<?php

declare(strict_types=1);

namespace Quantum\Routing\Dispatching;

use Closure;
use Quantum\Http\Request;
use Quantum\Routing\Dispatching\Contracts\DispatcherInterface;
use Quantum\Routing\RouteMatch;

final class ClosureDispatcher implements DispatcherInterface
{
    public function __construct(private readonly RouteArgumentResolver $arguments) {}

    public function dispatch(RouteMatch $match, Request $request): mixed
    {
        /** @var Closure $action */
        $action = $match->route()->action();
        $arguments = $this->arguments->forCallable(
            $action,
            $request,
            $match->parameters(),
            $match->route()->uri(),
        );

        return $action(...$arguments);
    }
}
