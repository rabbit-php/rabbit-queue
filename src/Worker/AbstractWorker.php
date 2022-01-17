<?php

declare(strict_types=1);

namespace Rabbit\Queue\Worker;

use Rabbit\Queue\IArrayHandler;

abstract class AbstractWorker implements IQueueWorker
{
    public function __construct(protected ?IArrayHandler $handler = null)
    {
    }
}
