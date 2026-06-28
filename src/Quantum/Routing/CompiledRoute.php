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
use VoltStack\Runtime\Component\Component;
use VoltStack\Runtime\Component\ComponentManager;

class CompiledRoute
{
    private RouteDefinition $definition;

    private string $pattern;

    /**
     * @var array<int, string>
     */
    private array $parameterNames;

    public function __construct(RouteDefinition $definition)
    {
        $this->definition = $definition;
        $this->recompile();
    }

    public function definition(): RouteDefinition
    {
        return $this->definition;
    }

    /**
     * @return array<int, string>
     */
    public function methods(): array
    {
        return $this->definition->methods();
    }

    public function uri(): string
    {
        return $this->definition->uri();
    }

    public function action(): mixed
    {
        return $this->definition->action();
    }

    public function routeName(): ?string
    {
        return $this->definition->name();
    }

    public function pattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return array<int, string>
     */
    public function parameterNames(): array
    {
        return $this->parameterNames;
    }

    public function allowsMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods(), true);
    }

    /**
     * @return array<string, string>|null
     */
    public function matches(Request $request): ?array
    {
        if (! $this->allowsMethod($request->method())) {
            return null;
        }

        return $this->matchPath($request->path());
    }

    /**
     * @return array<string, string>|null
     */
    public function matchPath(string $path): ?array
    {
        if (! preg_match($this->pattern, $path, $matches)) {
            return null;
        }

        array_shift($matches);

        $parameters = [];

        foreach ($this->parameterNames as $index => $name) {
            $parameters[$name] = $matches[$index] ?? null;
        }

        return $parameters;
    }

    public function run(Application $app, Request $request): mixed
    {
        $parameters = $request->routeParameters();
        $action = $this->action();

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
            if (is_subclass_of($action, Component::class)) {
                return $app->make(ComponentManager::class)->mount($action, $parameters, $request);
            }

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

    protected function replaceDefinition(RouteDefinition $definition): void
    {
        $this->definition = $definition;
        $this->recompile();
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
            $this->uri(),
        ));
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    private function compilePattern(): array
    {
        $uri = $this->definition->uri();
        $constraints = $this->definition->constraints();

        preg_match_all('/\{([^}]+)\}/', $uri, $parameterMatches);
        $parameterNames = $parameterMatches[1];

        $segments = explode('/', trim($uri, '/'));
        $compiledSegments = [];

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            if (preg_match('/^\{([^}]+)\}$/', $segment) === 1) {
                preg_match('/^\{([^}]+)\}$/', $segment, $parameterMatch);
                $parameterName = $parameterMatch[1];
                $constraint = $constraints[$parameterName] ?? '[^\/]+';
                $compiledSegments[] = '(' . $constraint . ')';
                continue;
            }

            $compiledSegments[] = preg_quote($segment, '/');
        }

        $pattern = '/^';
        $pattern .= $compiledSegments === [] ? '\/' : '\/' . implode('\/', $compiledSegments);
        $pattern .= '$/';

        return [$pattern, $parameterNames];
    }

    private function recompile(): void
    {
        [$pattern, $parameterNames] = $this->compilePattern();
        $this->pattern = $pattern;
        $this->parameterNames = $parameterNames;
    }
}
