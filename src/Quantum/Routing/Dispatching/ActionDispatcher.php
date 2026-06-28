<?php

declare(strict_types=1);

namespace Quantum\Routing\Dispatching;

use Quantum\Actions\Action;
use Quantum\Http\Request;
use Quantum\Routing\Dispatching\Contracts\DispatcherInterface;
use Quantum\Routing\RouteMatch;
use RuntimeException;
use VoltStack\Framework\Application;

final class ActionDispatcher implements DispatcherInterface
{
    public function __construct(private readonly Application $app) {}

    public function dispatch(RouteMatch $match, Request $request): mixed
    {
        $actionClass = $match->route()->action();

        if (! is_string($actionClass) || ! class_exists($actionClass) || ! is_subclass_of($actionClass, Action::class)) {
            throw new RuntimeException('Unsupported action route endpoint.');
        }

        $instance = $this->app->make($actionClass);

        return $instance->handle($request, ...array_values($match->parameters()));
    }
}
