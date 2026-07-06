<?php

declare(strict_types=1);

namespace Quantum\Routing\Contracts;

use Quantum\Http\Request;

interface RouteBindableInterface
{
    public static function resolveRouteBinding(string $value, string $parameter, Request $request): mixed;
}
