<?php

declare(strict_types=1);

namespace Rabbit\Queue;

use Rabbit\Base\Contract\ArrayAble;

abstract class AbstractJob implements ArrayAble
{
    public function __construct(array $configs = [])
    {
        $configs && configure($this, $configs);
    }

    abstract public function __invoke(array $params): void;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
