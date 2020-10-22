<?php

declare(strict_types=1);

namespace Rabbit\Queue\Worker;

use Rabbit\Queue\Queue;

interface IQueueWorker
{
    public function process(array $msg, Queue $queue): void;
}
