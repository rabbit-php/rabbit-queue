<?php

declare(strict_types=1);

namespace Rabbit\Queue\Driver;

interface DriverInterface
{
    public function getChannel(): string;
    public function get(string $id): array;
    public function push(array $message, int $delay = 0): ?string;
    public function pop(int $index = 0): array;
    public function remove(array $id): void;
    public function success(array $id): void;
    public function clear(): void;
    public function retry(): void;
}
