<?php

declare(strict_types=1);


namespace Rabbit\Queue\Serializer;

interface SerializerInterface
{
    public function serialize($object): string;
    public function unSerialize(string $serialized);
}
