<?php

declare(strict_types=1);

namespace Rabbit\Queue\Worker;

use Closure;
use Throwable;
use Rabbit\Base\App;
use Rabbit\Queue\Serializer\Factory;
use Rabbit\Base\Helper\ExceptionHelper;
use Rabbit\Queue\Queue;

class CoWorker extends AbstractWorker
{
    public function process(array $msg, Queue $queue): array
    {
        $ackIds = [];
        $rmIds = [];
        wgeach($msg, function ($id, $m) use (&$ackIds, &$rmIds): void {
            try {
                $type = $m['type'] ?? Factory::SERIALIZER_TYPE_NULL;
                $job = $type === Factory::SERIALIZER_TYPE_NULL ? $m['msg'] : Factory::getInstance($type)->unserialize($m['msg']);
                if ($job instanceof Closure) {
                    $job();
                } elseif (is_array($job)) {
                    [$class, $params] = $job;
                    create($class)($params);
                } elseif ($this->handler) {
                    $handler = $this->handler;
                    $handler($job);
                }
                $ackIds[] = $id;
            } catch (Throwable $e) {
                $rmIds[] = $id;
                App::error(ExceptionHelper::dumpExceptionToString($e));
            }
        });
        return [$ackIds, $rmIds];
    }
}
