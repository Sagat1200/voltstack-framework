<?php

declare(strict_types=1);

namespace Quantum\Actions;

abstract class Action
{
    public static function run(mixed ...$arguments): mixed
    {
        $action = app(static::class);

        return $action->handle(...$arguments);
    }

    abstract public function handle(mixed ...$arguments): mixed;
}
