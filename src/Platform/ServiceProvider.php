<?php

declare(strict_types=1);

namespace VoltStack\Framework;

use Quantum\Console\Command;

abstract class ServiceProvider
{
    public function __construct(protected Application $app) {}

    abstract public function register(): void;

    public function boot(): void {}

    /**
     * @return array<int, class-string<Command>>
     */
    public function commands(): array
    {
        return [];
    }
}
