<?php

declare(strict_types=1);

namespace Rabbit\Queue\Serializer;

use function Opis\Closure\serialize;
use function Opis\Closure\unserialize;

use Rabbit\Base\Exception\InvalidArgumentException;

class ClosureSerializer implements SerializerInterface
{
    public function serialize($closure): string
    {
        if (!is_callable($closure)) {
            throw new InvalidArgumentException('Argument invalid, it must be callable.');
        }

        return serialize($closure);
    }

    public function unserialize(string $serialized)
    {
        return unserialize($serialized);
    }
}
