<?php

declare(strict_types=1);

namespace Quantum\Routing\Dispatching;

use Closure;
use Quantum\Http\Request;
use Quantum\Routing\Contracts\RouteBindableInterface;
use Quantum\Routing\Exceptions\MissingRouteBindingException;
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
     * @param array<string, string> $parameterAliases
     */
    public function forCallable(
        callable $callable,
        Request $request,
        array $parameters,
        string $routeUri,
        array $parameterAliases = [],
    ): array {
        $reflection = new ReflectionFunction(Closure::fromCallable($callable));

        return $this->resolveParameters($reflection->getParameters(), $request, $parameters, $routeUri, $parameterAliases);
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, string> $parameterAliases
     */
    public function forMethod(
        object $instance,
        string $method,
        Request $request,
        array $parameters,
        string $routeUri,
        array $parameterAliases = [],
    ): array {
        $reflection = new ReflectionMethod($instance, $method);

        return $this->resolveParameters($reflection->getParameters(), $request, $parameters, $routeUri, $parameterAliases);
    }

    /**
     * @param array<int, ReflectionParameter> $reflectionParameters
     * @param array<string, mixed> $parameters
     * @param array<string, string> $parameterAliases
     * @return array<int, mixed>
     */
    private function resolveParameters(
        array $reflectionParameters,
        Request $request,
        array $parameters,
        string $routeUri,
        array $parameterAliases,
    ): array {
        $arguments = [];

        foreach ($reflectionParameters as $parameter) {
            $arguments[] = $this->resolveParameter($parameter, $request, $parameters, $routeUri, $parameterAliases);
        }

        return $arguments;
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, string> $parameterAliases
     */
    private function resolveParameter(
        ReflectionParameter $parameter,
        Request $request,
        array $parameters,
        string $routeUri,
        array $parameterAliases,
    ): mixed {
        if (array_key_exists($parameter->getName(), $parameters)) {
            $parameterKey = $parameter->getName();
            $parameterValue = $parameters[$parameterKey];
        } else {
            $aliasedParameter = $parameterAliases[$parameter->getName()] ?? null;

            if (is_string($aliasedParameter) && array_key_exists($aliasedParameter, $parameters)) {
                $parameterKey = $aliasedParameter;
                $parameterValue = $parameters[$aliasedParameter];
            } else {
                $parameterKey = null;
                $parameterValue = null;
            }
        }

        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $typeName = $type->getName();

            if ($typeName === Request::class) {
                return $request;
            }

            if (is_string($parameterKey) && is_subclass_of($typeName, RouteBindableInterface::class)) {
                $binding = $typeName::resolveRouteBinding((string) $parameterValue, $parameterKey, $request);

                if ($binding === null) {
                    throw new MissingRouteBindingException(
                        $parameter->getName(),
                        $routeUri,
                        $parameterKey,
                        (string) $parameterValue,
                        $typeName,
                    );
                }

                return $binding;
            }

            return $this->app->make($typeName);
        }

        if (is_string($parameterKey)) {
            return $parameterValue;
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
