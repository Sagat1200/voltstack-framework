<?php

declare(strict_types=1);

namespace Quantum\Facades;

use Quantum\Routing\Router;

final class Route extends Facade
{
    protected static function accessor(): string
    {
        return Router::class;
    }
}
