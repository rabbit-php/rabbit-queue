<?php

declare(strict_types=1);

namespace Rabbit\Queue;

use Rabbit\Queue\Serializer\Factory;

class Queue extends AbstractQueue
{
    public function push($msg): ?string
    {
        if (is_callable($msg)) {
            $serializedMessage = Factory::getInstance(Factory::SERIALIZER_TYPE_CLOSURE)->serialize($msg);
            $serializerType = Factory::SERIALIZER_TYPE_CLOSURE;
        } elseif ($msg instanceof JobInterface) {
            $serializedMessage = $this->phpSerializer->serialize($msg);
            $serializerType = Factory::SERIALIZER_TYPE_PHP;
        } else {
            $serializedMessage = &$msg;
            $serializerType = Factory::SERIALIZER_TYPE_NULL;
        }
        return $this->driver->push(['msg' => &$serializedMessage, 'type' => $serializerType]);
    }
}
