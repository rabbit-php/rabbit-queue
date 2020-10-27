<?php

declare(strict_types=1);

namespace Rabbit\Queue\Driver;


abstract class AbstractDriver implements DriverInterface
{
    protected string $channel = 'default';
    const STATUS_WAITING = 1;
    const STATUS_RESERVED = 2;
    const STATUS_DONE = 3;
    const STATUS_FAILED = 4;

    public function __construct(string $channel)
    {
        $this->channel = $channel;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }
}
