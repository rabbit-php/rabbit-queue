<?php

declare(strict_types=1);

namespace Rabbit\Queue\Serializer;

use Rabbit\Base\Exception\InvalidArgumentException;
use SuperClosure\Serializer as SuperClosureSerializer;

class ClosureSerializer implements SerializerInterface
{
    protected $executor;

    public function __construct()
    {
        $this->executor = new SuperClosureSerializer();
    }

    public function serialize($closure): string
    {
        if (!is_callable($closure)) {
            throw new InvalidArgumentException('Argument invalid, it must be callable.');
        }

        return $this->executor->serialize($closure);
    }

    public function unSerialize(string $serialized)
    {
        return $this->executor->unserialize($serialized);
    }
}
