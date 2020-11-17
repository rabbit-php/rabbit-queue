<?php

declare(strict_types=1);

namespace Rabbit\Queue\Serializer;

use Rabbit\Base\Exception\InvalidArgumentException;

class Factory
{
    const SERIALIZER_TYPE_JSON = 'json_serializer';
    const SERIALIZER_TYPE_CLOSURE = 'closure_serializer';
    const SERIALIZER_TYPE_COMPRESSING = 'compressing_serializer';
    const SERIALIZER_TYPE_NULL = 'null_serializer';
    public static $instances = [];

    public static function getInstance(string $type): SerializerInterface
    {
        if (!in_array($type, [self::SERIALIZER_TYPE_JSON, self::SERIALIZER_TYPE_CLOSURE, self::SERIALIZER_TYPE_COMPRESSING], true)) {
            throw new InvalidArgumentException("The arg type: {$type} is invalid.");
        }
        if (!isset(self::$instances[$type])) {
            self::$instances[$type] = self::make($type);
        }

        return self::$instances[$type];
    }

    public static function make(string $type): ?SerializerInterface
    {
        switch ($type) {
            case self::SERIALIZER_TYPE_JSON:
                return new JsonSerializer();
            case self::SERIALIZER_TYPE_CLOSURE:
                return new ClosureSerializer();
            default:
                return null;
        }
    }
}
