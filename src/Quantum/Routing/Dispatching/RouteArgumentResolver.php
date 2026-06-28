<?php

declare(strict_types=1);

namespace Quantum\Routing\Dispatching;

use Closure;
use Quantum\Http\Request;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use VoltStack\Framework\Application;

final class RouteArgumentResolver
{
    public function __construct(private readonly Application $app) {}

    /**
     * @param array<string, mixed> $parameters
     */
    public function forCallable(callable $callable, Request $request, array $parameters, string $routeUri): array
    {
        $reflection = new ReflectionFunction(Closure::fromCallable($callable));

        return $this->resolveParameters($reflection->getParameters(), $request, $parameters, $routeUri);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function forMethod(
        object $instance,
        string $method,
        Request $request,
        array $parameters,
        string $routeUri,
    ): array {
        $reflection = new ReflectionMethod($instance, $method);

        return $this->resolveParameters($reflection->getParameters(), $request, $parameters, $routeUri);
    }

    /**
     * @param array<int, ReflectionParameter> $reflectionParameters
     * @param array<string, mixed> $parameters
     * @return array<int, mixed>
     */
    private function resolveParameters(
        array $reflectionParameters,
        Request $request,
        array $parameters,
        string $routeUri,
    ): array {
        $arguments = [];

        foreach ($reflectionParameters as $parameter) {
            $arguments[] = $this->resolveParameter($parameter, $request, $parameters, $routeUri);
        }

        return $arguments;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function resolveParameter(
        ReflectionParameter $parameter,
        Request $request,
        array $parameters,
        string $routeUri,
    ): mixed {
        if (array_key_exists($parameter->getName(), $parameters)) {
            return $parameters[$parameter->getName()];
        }

        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $typeName = $type->getName();

            if ($typeName === Request::class) {
                return $request;
            }

            return $this->app->make($typeName);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new RuntimeException(sprintf(
            'Unable to resolve route argument [%s] for route [%s].',
            $parameter->getName(),
            $routeUri,
        ));
    }
}
