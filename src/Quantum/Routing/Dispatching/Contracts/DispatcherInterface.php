<?php

declare(strict_types=1);

namespace Quantum\Routing\Dispatching\Contracts;

use Quantum\Http\Request;
use Quantum\Routing\RouteMatch;

interface DispatcherInterface
{
    public function dispatch(RouteMatch $match, Request $request): mixed;
}
