<?php

declare(strict_types=1);

namespace Quantum\Routing\Dispatching;

use Closure;
use Quantum\Actions\Action;
use Quantum\Http\Request;
use Quantum\Routing\Dispatching\Contracts\DispatcherInterface;
use Quantum\Routing\RouteMatch;
use RuntimeException;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\Component;

final class DispatcherResolver
{
    public function __construct(private readonly Application $app) {}

    public function dispatch(RouteMatch $match, Request $request): mixed
    {
        return $this->resolve($match)->dispatch($match, $request);
    }

    public function resolve(RouteMatch $match): DispatcherInterface
    {
        $action = $match->route()->action();

        if ($action instanceof Closure) {
            return $this->app->make(ClosureDispatcher::class);
        }

        if (is_string($action) && class_exists($action) && is_subclass_of($action, Component::class)) {
            return $this->app->make(ComponentDispatcher::class);
        }

        if ($this->isActionClass($action)) {
            return $this->app->make(ActionDispatcher::class);
        }

        if ($this->isControllerAction($action)) {
            return $this->app->make(ControllerDispatcher::class);
        }

        if (is_callable($action)) {
            return $this->app->make(ClosureDispatcher::class);
        }

        throw new RuntimeException('Unsupported route action.');
    }

    private function isActionClass(mixed $action): bool
    {
        return is_string($action) && class_exists($action) && is_subclass_of($action, Action::class);
    }

    private function isControllerAction(mixed $action): bool
    {
        if (is_array($action) && count($action) === 2) {
            return true;
        }

        if (is_string($action) && str_contains($action, '@')) {
            return true;
        }

        return is_string($action) && class_exists($action);
    }
}
