<?php

declare(strict_types=1);

namespace Rabbit\Queue\Driver;

use Closure;
use Throwable;
use Rabbit\Base\App;
use Rabbit\DB\Redis\Redis;
use Rabbit\Pool\BaseManager;
use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\Base\Contract\InitInterface;
use Rabbit\Queue\AbstractQueue;

class RedisQueue extends AbstractDriver implements InitInterface
{
    protected Redis $redis;

    protected string $group = 'rabbit';

    protected string $consumer = 'rabbit';

    protected int $batch = 1;

    protected int $mvCount = 10;

    protected int $minIdleTime = 600;

    protected int $maxDelivery = 3;

    public function __construct(BaseManager $manager, string $redisKey = 'ext')
    {
        $this->redis = $manager->get($redisKey);
    }

    public function init(): void
    {
        $this->create();
        $this->createGroupIfNotExists();
    }

    public function get(string $id): array
    {
        $newItem = $this->redis->xRead([$this->channel => $id]);
        return $newItem[$this->channel] ?? [];
    }

    public function push(array $message, int $delay = 0): ?string
    {
        return $this->redis->xAdd($this->channel, '*', $message);
    }

    public function pop(Closure $func, AbstractQueue $queue, int $index = 0): void
    {
        while ($queue->running) {
            $pool = $this->redis->getPool();
            $client = $pool->get();
            try {
                $newItem = $client->xReadGroup(
                    $this->group,
                    $this->consumer . '-' . (App::$id ?? 0) . '-' . $index,
                    [$this->channel => '>'],
                    $this->batch,
                    0
                );
                if ($newItem === false) {
                    $pool->sub();
                } else {
                    $client->release();
                }
            } catch (Throwable $e) {
                $pool->sub();
                App::error($e->getMessage());
            }
            if (!isset($newItem[$this->channel])) {
                return;
            }
            [$ackIds, $rmIds] = $func($newItem[$this->channel]);
            $ackIds && $this->success($ackIds);
            $rmIds && $this->remove($rmIds);
        }
    }

    public function remove(array $ids): void
    {
        $this->redis->xDel($this->channel, $ids);
    }

    public function success(array $ids): void
    {
        $this->redis->xAck($this->channel, $this->group, $ids);
    }

    public function clear(): void
    {
        $this->redis->xTrim($this->channel, 0);
    }

    public function retry(): void
    {
        $pendingItems = $this->getRunningJobs($this->mvCount);
        if ($pendingItems) {
            $claimIds = [];
            $rmIds = [];
            foreach ($pendingItems as $pendingItem) {
                [$msgId, $consumer,,, $deliveryCnt] = $pendingItem;
                $claimIds[] = $msgId;
                if ($deliveryCnt > $this->maxDelivery) {
                    $rmIds[] = $msgId;
                }
            }
            $claimIds && $this->claim($claimIds, $consumer);
            $rmIds && $this->remove($rmIds);
        }
    }

    public function claim(array $ids, string $name): void
    {
        $consumers = ArrayHelper::getColumn($this->getConsumers(), 'name', []);
        unset($consumers[$name]);
        $consumers && $this->redis->xClaim(
            $this->channel,
            $this->group,
            $consumers[array_rand($consumers)],
            $this->minIdleTime,
            $ids
        );
    }

    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-10-22
     * @return void
     */
    public function create()
    {
        $client = $this->redis->getPool()->get();
        if (!$client->exists($this->channel)) {
            $client->xDel($this->channel, [$client->xAdd($this->channel, '*', ['hello' => 'world'])]);
        }
        $client->release();
    }

    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-10-22
     * @return void
     */
    protected function createGroupIfNotExists(): void
    {
        $client = $this->redis->getPool()->get();
        $groups = $client->xInfo('GROUPS', 'default');
        $exists = $groups ? (array_search('rabbit', array_column($groups, 'name')) !== false) : false;
        if ($exists === false) {
            $client->xGroup('CREATE', 'default', 'rabbit', '0');
        }
        $client->release();
    }

    public function getConsumers(): array
    {
        return $this->redis->xInfo('CONSUMERS', $this->channel, $this->group);
    }

    public function getRunningJobs(int $limit, $start = '-', $end = '+')
    {
        $jobs = $this->redis->xPending(
            $this->channel,
            $this->group,
            $start,
            $end,
            $limit
        );
        return is_array($jobs) ? $jobs : [];
    }

    public function getRunningCount()
    {
        $pendingJobs = $this->redis->xPending($this->channel, $this->group);
        return $pendingJobs ? $pendingJobs[0] : 0;
    }
}
