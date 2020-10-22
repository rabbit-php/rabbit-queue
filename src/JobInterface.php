<?php

declare(strict_types=1);

namespace Rabbit\Queue;

interface JobInterface
{
    public function __invoke(): void;
}
