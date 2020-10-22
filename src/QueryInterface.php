<?php

declare(strict_types=1);

namespace Rabbit\Queue;

use Rabbit\Queue\Driver\DriverInterface;

interface QueryInterface
{
    public function getSleep(): float;
    public function getDrvier(): DriverInterface;
    public function push($msg): ?string;
    public function run(string $id): void;
    public function listen(): void;
    public function stop(): void;
}
