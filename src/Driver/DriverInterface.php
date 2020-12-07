<?php

declare(strict_types=1);

namespace Rabbit\Queue\Driver;

use Closure;
use Rabbit\Queue\AbstractQueue;

interface DriverInterface
{
    public function getChannel(): string;
    public function get(string $id): array;
    public function push(array $message, int $delay = 0): ?string;
    public function pop(Closure $func, AbstractQueue $queue, int $index = 0): void;
    public function remove(array $id): void;
    public function success(array $id): void;
    public function clear(): void;
    public function retry(): void;
}
