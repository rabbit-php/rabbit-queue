<?php

declare(strict_types=1);

namespace Rabbit\Queue\Driver;

use Closure;
use Rabbit\Base\Exception\NotSupportedException;
use Rabbit\Nsq\Consumer;
use Rabbit\Nsq\NsqManager;
use Rabbit\Nsq\Producer;
use Rabbit\Queue\AbstractQueue;
use RuntimeException;

class NsqQueue extends AbstractDriver
{
    protected string $topic;
    protected ?Producer $producer = null;
    protected ?Consumer $consumer = null;
    protected array $config = [];
    protected string $key;
    protected NsqManager $manager;

    public function __construct(NsqManager $manager, string $channel = 'rabbit-queue', string $topic = 'queue', string $key = 'default')
    {
        parent::__construct($channel);
        $this->topic = $topic;
        $this->key = $key;
        $this->manager = $manager;
    }

    public function push(array $message, int $delay = 0): ?string
    {
        if ($this->producer === null) {
            $this->producer = $this->manager->getProducer($this->key);
            $this->producer->makeTopic($this->topic);
        }
        $this->producer->publish($this->topic, json_encode($message));
        return null;
    }

    public function pop(Closure $func, AbstractQueue $queue, int $index = 0): void
    {
        if ($this->consumer === null) {
            $this->consumer = $this->manager->getConsumer($this->key);
            $this->consumer->makeTopic($this->topic, $this->channel);
        }
        $this->consumer->subscribe($this->topic, $this->channel, $this->config, function (array $message) use ($func) {
            [$ackIds] = $func([$message['id'] => json_decode($message['payload'], true)]);
            if (empty($ackIds)) {
                throw new RuntimeException('Queue error!');
            }
        }, $queue->running);
    }

    public function get(string $id): array
    {
        throw new NotSupportedException("Not support get!");
    }

    public function remove(array $id): void
    {
        throw new NotSupportedException("Not support remove!");
    }

    public function success(array $id): void
    {
        throw new NotSupportedException("Not support success!");
    }

    public function retry(): void
    {
        throw new NotSupportedException("Not support retry!");
    }

    public function clear(): void
    {
        throw new NotSupportedException("Not support clear!");
    }
}
