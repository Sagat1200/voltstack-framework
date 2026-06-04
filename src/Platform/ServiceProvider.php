<?php

declare(strict_types=1);

namespace VoltStack\Framework;

abstract class ServiceProvider
{
    public function __construct(protected Application $app)
    {
    }

    abstract public function register(): void;

    public function boot(): void
    {
    }
}
