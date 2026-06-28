<?php

declare(strict_types=1);

namespace Quantum\Routing\Dispatching;

use Quantum\Http\Request;
use Quantum\Routing\Dispatching\Contracts\DispatcherInterface;
use Quantum\Routing\RouteMatch;
use VoltStack\Runtime\Component\ComponentManager;

final class ComponentDispatcher implements DispatcherInterface
{
    public function __construct(private readonly ComponentManager $components) {}

    public function dispatch(RouteMatch $match, Request $request): mixed
    {
        /** @var class-string $action */
        $action = $match->route()->action();

        return $this->components->mount($action, $match->parameters(), $request);
    }
}
