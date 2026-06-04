<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Closure;
use Quantum\Http\Request;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use VoltStack\Framework\Application;

final class Route
{
    /**
     * @param array<int, string> $methods
     */
    public function __construct(
        private array $methods,
        private string $uri,
        private mixed $action,
    ) {
        $this->methods = array_map('strtoupper', $methods);
        $this->uri = $this->normalizeUri($uri);
    }

    /**
     * @return array<int, string>
     */
    public function methods(): array
    {
        return $this->methods;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function action(): mixed
    {
        return $this->action;
    }

    /**
     * @return array<string, string>|null
     */
    public function matches(Request $request): ?array
    {
        if (! in_array($request->method(), $this->methods, true)) {
            return null;
        }

        [$pattern, $parameterNames] = $this->compilePattern();

        if (! preg_match($pattern, $request->path(), $matches)) {
            return null;
        }

        array_shift($matches);

        $parameters = [];

        foreach ($parameterNames as $index => $name) {
            $parameters[$name] = $matches[$index] ?? null;
        }

        return $parameters;
    }

    public function run(Application $app, Request $request): mixed
    {
        $parameters = $request->routeParameters();
        $action = $this->action;

        if ($action instanceof Closure) {
            return $this->invokeCallable($app, $request, $action, $parameters);
        }

        if (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
            $instance = is_object($class) ? $class : $app->make((string) $class);

            return $this->invokeMethod($app, $request, $instance, (string) $method, $parameters);
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);
            $instance = $app->make($class);

            return $this->invokeMethod($app, $request, $instance, $method, $parameters);
        }

        if (is_string($action) && class_exists($action)) {
            $instance = $app->make($action);

            if (! method_exists($instance, '__invoke')) {
                throw new RuntimeException(sprintf('Route action [%s] is not invokable.', $action));
            }

            return $this->invokeMethod($app, $request, $instance, '__invoke', $parameters);
        }

        if (is_callable($action)) {
            return $this->invokeCallable($app, $request, $action, $parameters);
        }

        throw new RuntimeException('Unsupported route action.');
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function invokeCallable(Application $app, Request $request, callable $callable, array $parameters): mixed
    {
        $reflection = new ReflectionFunction(Closure::fromCallable($callable));
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $arguments[] = $this->resolveArgument($app, $request, $parameter, $parameters);
        }

        return $callable(...$arguments);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function invokeMethod(
        Application $app,
        Request $request,
        object $instance,
        string $method,
        array $parameters,
    ): mixed {
        $reflection = new ReflectionMethod($instance, $method);
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $arguments[] = $this->resolveArgument($app, $request, $parameter, $parameters);
        }

        return $reflection->invokeArgs($instance, $arguments);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function resolveArgument(
        Application $app,
        Request $request,
        ReflectionParameter $parameter,
        array $parameters,
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

            return $app->make($typeName);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new RuntimeException(sprintf(
            'Unable to resolve route argument [%s] for route [%s].',
            $parameter->getName(),
            $this->uri,
        ));
    }

    private function normalizeUri(string $uri): string
    {
        if ($uri === '') {
            return '/';
        }

        $normalized = '/' . trim($uri, '/');

        return $normalized === '//' ? '/' : $normalized;
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    private function compilePattern(): array
    {
        preg_match_all('/\{([^}]+)\}/', $this->uri, $parameterMatches);
        $parameterNames = $parameterMatches[1];

        $segments = explode('/', trim($this->uri, '/'));
        $compiledSegments = [];

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            if (preg_match('/^\{([^}]+)\}$/', $segment) === 1) {
                $compiledSegments[] = '([^\/]+)';
                continue;
            }

            $compiledSegments[] = preg_quote($segment, '/');
        }

        $pattern = '/^';
        $pattern .= $compiledSegments === [] ? '\/' : '\/' . implode('\/', $compiledSegments);
        $pattern .= '$/';

        return [$pattern, $parameterNames];
    }
}
