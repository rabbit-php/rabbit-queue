<?php

declare(strict_types=1);

namespace Rabbit\Queue\Serializer;

use Rabbit\Base\Exception\InvalidArgumentException;

class JsonSerializer implements SerializerInterface
{
    public function serialize($object): string
    {
        if (!is_array($object)) {
            throw new InvalidArgumentException('Argument invalid, it must be an object.');
        }

        return json_encode($object);
    }

    public function unserialize(string $serialized)
    {
        return json_decode($serialized, true);
    }
}
