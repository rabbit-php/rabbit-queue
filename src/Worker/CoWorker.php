<?php

declare(strict_types=1);

namespace Rabbit\Queue\Worker;

use Throwable;
use Rabbit\Base\App;
use Rabbit\Queue\Queue;
use Swoole\Coroutine\System;
use Rabbit\Queue\JobInterface;
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
                if (is_callable($job) || ($job instanceof JobInterface)) {
                    $job();
                } elseif ($this->handler) {
                    $handler = $this->handler;
                    $handler($job);
                }
            } catch (Throwable $e) {
                $rmIds[] = $id;
                App::error(ExceptionHelper::dumpExceptionToString($e));
                System::sleep($queue->getSleep());
            } finally {
                $ackIds[] = $id;
            }
        });
        $ackIds && $queue->getDrvier()->success($ackIds);
        $rmIds && $queue->getDrvier()->remove($rmIds);
    }
}
