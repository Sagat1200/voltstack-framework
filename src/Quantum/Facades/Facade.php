<?php

declare(strict_types=1);

namespace Quantum\Facades;

use RuntimeException;
use VoltStack\Framework\Application;

abstract class Facade
{
    abstract protected static function accessor(): string;

    protected static function resolveRoot(): object
    {
        $app = Application::getInstance();

        if ($app === null) {
            throw new RuntimeException('The VoltStack application instance has not been bootstrapped.');
        }

        $resolved = $app->make(static::accessor());

        if (! is_object($resolved)) {
            throw new RuntimeException(sprintf(
                'Facade [%s] could not resolve an object root.',
                static::class,
            ));
        }

        return $resolved;
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::resolveRoot()->{$method}(...$arguments);
    }
}
