<?php

declare(strict_types=1);

namespace Rabbit\Queue\Driver;

use Closure;
use longlang\phpkafka\Consumer\ConsumeMessage;
use longlang\phpkafka\Consumer\Consumer;
use longlang\phpkafka\Producer\Producer;
use Rabbit\Base\Exception\NotSupportedException;
use Rabbit\Kafka\KafkaManager;
use Rabbit\Queue\AbstractQueue;
use RuntimeException;

class KafkaQueue extends AbstractDriver
{
    protected string $client;
    protected string $key;
    protected ?Producer $producer = null;
    protected KafkaManager $manager;
    protected ?Consumer $consumer = null;

    public function __construct(KafkaManager $manager, string $channel = 'rabbit-queue', string $client = 'queue', string $key = 'default')
    {
        parent::__construct($channel);
        $this->client = $client;
        $this->key = $key;
        $this->manager = $manager;
    }

    public function push(array $message, int $delay = 0): ?string
    {
        if ($this->producer === null) {
            $this->producer = $this->manager->getProducer($this->key);
        }
        $this->producer->send($this->channel, json_encode($message));
        return null;
    }

    public function pop(Closure $func, AbstractQueue $queue, int $index = 0): void
    {
        if ($this->consumer === null) {
            $config = $this->manager->getConsumerConfig();
            $config->setTopic($this->channel);
            $config->setGroupId("$this->client-group");
            $config->setClientId($this->client);
            $this->consumer = new Consumer($config);
        }

        while ($queue->running) {
            $message = $this->consumer->consume();
            if ($message) {
                [$ackIds] = $func([$message->getPartition() => json_decode($message->getValue(), true)]);
                if (empty($ackIds)) {
                    throw new RuntimeException('Queue error!');
                }
                $this->success($ackIds);
            } else {
                usleep((int) ($this->consumer->getConfig()->getInterval() * 1000000));
            }
        }
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
        foreach ($id as $i) {
            $this->consumer->ack((int)$i); // ack
        }
    }

    public function retry(): void
    {
        throw new NotSupportedException("Not support retry!");
    }

    public function clear(): void
    {
        throw new NotSupportedException("Not support clear!");
    }
}
