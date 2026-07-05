<?php

declare(strict_types=1);

namespace Quantum\Routing\Dispatching;

use Quantum\Http\Request;
use Quantum\Routing\Dispatching\Contracts\DispatcherInterface;
use Quantum\Routing\RouteMatch;
use RuntimeException;
use VoltStack\Framework\Application;

final class ControllerDispatcher implements DispatcherInterface
{
    public function __construct(
        private readonly Application $app,
        private readonly RouteArgumentResolver $arguments,
    ) {}

    public function dispatch(RouteMatch $match, Request $request): mixed
    {
        [$instance, $method] = $this->resolveTarget($match->route()->action());
        $parameterAliases = $match->route()->routeMetadata()->get('parameter_aliases', []);
        $arguments = $this->arguments->forMethod(
            $instance,
            $method,
            $request,
            $match->parameters(),
            $match->route()->uri(),
            is_array($parameterAliases) ? $parameterAliases : [],
        );

        return $instance->{$method}(...$arguments);
    }

    /**
     * @return array{0: object, 1: string}
     */
    private function resolveTarget(mixed $action): array
    {
        if (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;

            return [
                is_object($class) ? $class : $this->app->make((string) $class),
                (string) $method,
            ];
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);

            return [$this->app->make($class), $method];
        }

        if (is_string($action) && class_exists($action)) {
            $instance = $this->app->make($action);

            if (! method_exists($instance, '__invoke')) {
                throw new RuntimeException(sprintf('Route action [%s] is not invokable.', $action));
            }

            return [$instance, '__invoke'];
        }

        throw new RuntimeException('Unsupported controller route action.');
    }
}
