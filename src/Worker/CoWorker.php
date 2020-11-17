<?php

declare(strict_types=1);

namespace Rabbit\Queue\Worker;

use Closure;
use Throwable;
use Rabbit\Base\App;
use Rabbit\Queue\Queue;
use Rabbit\Queue\Serializer\Factory;
use Rabbit\Base\Helper\ExceptionHelper;

class CoWorker extends AbstractWorker
{
    public function process(array $msg, Queue $queue): void
    {
        $ackIds = [];
        $rmIds = [];
        wgeach($msg, function ($id, $m) use ($queue, &$ackIds, &$rmIds) {
            try {
                $type = $m['type'] ?? Factory::SERIALIZER_TYPE_NULL;
                $job = $type === Factory::SERIALIZER_TYPE_NULL ? $m['msg'] : Factory::getInstance($type)->unserialize($m['msg']);
                if ($job instanceof Closure) {
                    $job();
                } elseif (is_array($job)) {
                    [$class, $params] = $job;
                    getDI($class)($params);
                } elseif ($this->handler) {
                    $handler = $this->handler;
                    $handler($job);
                }
            } catch (Throwable $e) {
                $rmIds[] = $id;
                App::error(ExceptionHelper::dumpExceptionToString($e));
                usleep(intval($queue->getSleep() * 1000 * 1000));
            } finally {
                $ackIds[] = $id;
            }
        });
        $ackIds && $queue->getDrvier()->success($ackIds);
        $rmIds && $queue->getDrvier()->remove($rmIds);
    }
}
