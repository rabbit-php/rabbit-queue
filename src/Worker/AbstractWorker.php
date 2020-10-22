<?php

declare(strict_types=1);

namespace Rabbit\Queue\Worker;

use Rabbit\Queue\IArrayHandler;

abstract class AbstractWorker implements IQueueWorker
{
    protected ?IArrayHandler $handler;
    
    public function __construct(IArrayHandler $handler = null)
    {
        $this->handler = $handler;
    }
}
