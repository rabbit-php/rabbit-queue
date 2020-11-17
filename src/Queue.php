<?php

declare(strict_types=1);

namespace Rabbit\Queue;

use Closure;
use Rabbit\Queue\Serializer\Factory;

class Queue extends AbstractQueue
{
    public function push($msg): ?string
    {
        if ($msg instanceof Closure) {
            $serializedMessage = Factory::getInstance(Factory::SERIALIZER_TYPE_CLOSURE)->serialize($msg);
            $serializerType = Factory::SERIALIZER_TYPE_CLOSURE;
        } elseif ($msg instanceof AbstractJob) {
            $data = [get_class($msg), $msg->toArray() ?? []];
            $serializedMessage = Factory::getInstance(Factory::SERIALIZER_TYPE_JSON)->serialize($data);
            $serializerType = Factory::SERIALIZER_TYPE_JSON;
        } else {
            $serializedMessage = &$msg;
            $serializerType = Factory::SERIALIZER_TYPE_NULL;
        }
        return $this->driver->push(['msg' => &$serializedMessage, 'type' => $serializerType]);
    }
}
