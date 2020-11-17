<?php

declare(strict_types=1);

namespace Rabbit\Queue;

use Rabbit\Base\Contract\ArrayAble;

abstract class AbstractJob implements ArrayAble
{
    abstract public function __invoke(array $params): void;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
