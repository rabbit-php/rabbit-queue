<?php

declare(strict_types=1);

namespace Rabbit\Queue;

use Throwable;
use Rabbit\Base\App;
use Rabbit\Queue\Worker\IQueueWorker;
use Rabbit\Base\Helper\ExceptionHelper;
use Rabbit\Queue\Driver\DriverInterface;

abstract class AbstractQueue implements QueryInterface
{
    protected DriverInterface $driver;
    protected IQueueWorker $worker;
    public bool $running = true;
    protected float $sleep = 3.0;

    const LOG_KEY = 'queue';

    public function __construct(DriverInterface $driver, IQueueWorker $worker)
    {
        $this->driver = $driver;
        $this->worker = $worker;
    }

    public function getSleep(): float
    {
        return $this->sleep;
    }

    public function getDrvier(): DriverInterface
    {
        return $this->driver;
    }

    public function run(string $id): void
    {
        App::debug('Start ' . $this->driver->getChannel() . ' processing queue msg: ' . $id);
        $msg = $this->driver->get($id);
        $this->worker->process($msg, $this);
        App::debug(
            'Finish ' . $this->driver->getChannel() . ' processing queue msg: ' . $id,
            self::LOG_KEY
        );
    }

    public function listen(int $threadNum = 1): void
    {
        App::debug('Start listening to the queue ' . $this->driver->getChannel(), self::LOG_KEY);
        for ($i = 0; $i < $threadNum; $i++) {
            rgo(function () use ($i) {
                try {
                    $this->driver->pop(fn (array $msg) => $this->worker->process($msg, $this), $this, $i);
                } catch (Throwable $e) {
                    App::error(ExceptionHelper::dumpExceptionToString($e), self::LOG_KEY);
                }
            });
        }
    }

    public function stop(): void
    {
        $this->running = false;
    }
}
