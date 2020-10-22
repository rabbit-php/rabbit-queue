<?php

declare(strict_types=1);

namespace Rabbit\Queue\Serializer;

use Rabbit\Base\Exception\InvalidArgumentException;

class PhpSerializer implements SerializerInterface
{
    public function serialize($object): string
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException('Argument invalid, it must be an object.');
        }

        return serialize($object);
    }

    public function unSerialize(string $serialized)
    {
        return unserialize($serialized);
    }
}
