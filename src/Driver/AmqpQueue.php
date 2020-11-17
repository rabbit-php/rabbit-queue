<?php

declare(strict_types=1);

namespace Rabbit\Queue\Driver;

use Closure;
use PhpAmqpLib\Message\AMQPMessage;
use Rabbit\Base\Exception\NotSupportedException;
use Rabbit\Base\Helper\VarDumper;
use Rabbit\Pool\BaseManager;
use Rabbit\Pool\BasePool;
use RuntimeException;

class AmqpQueue extends AbstractDriver
{
    protected BasePool $conn;

    protected string $consumerTag;

    protected array $properties = [
        'content_type' => 'text/plain',
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
    ];

    public function __construct(BaseManager $manager, string $consumerTag = 'consumer', string $key = 'default')
    {
        $this->consumerTag = $consumerTag;
        $this->conn = $manager->get($key);
    }

    public function push(array $message, int $delay = 0): ?string
    {
        $amqp = $this->conn->get();
        return $amqp->basic_publish(new AMQPMessage(json_encode($message), $this->properties), $amqp->exchange);
    }

    public function pop(Closure $func, int $index = 0): void
    {
        $amqp = $this->conn->get();
        $this->conn->sub();
        $amqp->consume($this->consumerTag, false, false, false, false, function (AMQPMessage $msg) use ($func) {
            [$ackIds] = $func([$msg->getDeliveryTag() => json_decode($msg->getBody(), true)]);
            if (empty($ackIds)) {
                throw new RuntimeException('Queue error!' . PHP_EOL . VarDumper::getDumper()->dumpAsString($msg->delivery_info));
            }
        });
    }

    public function get(string $id): array
    {
        throw new NotSupportedException("Not support get!");
    }

    public function remove(array $id): void
    {
    }

    public function success(array $id): void
    {
        $client = $this->conn->get();
        $client->basic_ack(reset($id));
    }

    public function retry(): void
    {
        throw new NotSupportedException("Not support retry!");
    }

    public function clear(): void
    {
        $client = $this->conn->get();
        $client->queue_purge($client->queue);
    }
}
