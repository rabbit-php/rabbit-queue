<?php

declare(strict_types=1);

namespace Rabbit\Queue\Driver;

use Closure;
use PhpAmqpLib\Message\AMQPMessage;
use Rabbit\Base\Exception\NotSupportedException;
use Rabbit\Base\Helper\VarDumper;
use Rabbit\Pool\BaseManager;
use Rabbit\Pool\BasePool;
use Rabbit\Queue\AbstractQueue;
use RuntimeException;

class AmqpQueue extends AbstractDriver
{
    protected BasePool $conn;

    protected array $properties = [
        'content_type' => 'text/plain',
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
    ];

    public function __construct(BaseManager $manager, string $channel = 'rabbit-queue', string $key = 'default')
    {
        parent::__construct($channel);
        $this->conn = $manager->get($key);
    }

    public function push(array $message, int $delay = 0): ?string
    {
        $amqp = $this->conn->get();
        return $amqp->basic_publish(new AMQPMessage(json_encode($message), $this->properties), $amqp->exchange);
    }

    public function pop(Closure $func, AbstractQueue $queue, int $index = 0): void
    {
        $amqp = $this->conn->get();
        $this->conn->sub();
        $amqp->consume($this->channel, false, false, false, false, function (AMQPMessage $msg) use ($func): void {
            [$ackIds] = $func([$msg->getDeliveryTag() => json_decode($msg->getBody(), true)]);
            if (empty($ackIds)) {
                throw new RuntimeException('Queue error!' . PHP_EOL . VarDumper::getDumper()->dumpAsString($msg->delivery_info));
            }
        })->wait($queue->running);
    }

    public function get(string $id): array
    {
        throw new NotSupportedException("Not support get!");
    }

    public function remove(array $id): void
    {
        throw new NotSupportedException("Not support remove!");
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
