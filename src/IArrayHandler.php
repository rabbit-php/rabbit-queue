<?php

declare(strict_types=1);

namespace Rabbit\Queue;

interface IArrayHandler
{
    public function __invoke(string $msg): void;
}
