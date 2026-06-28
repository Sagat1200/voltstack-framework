<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Quantum\HttpKernel\MiddlewareAliasRegistry;
use Quantum\HttpKernel\MiddlewareStack;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\Routing\Dispatching\DispatcherResolver;
use Quantum\Routing\Exceptions\MethodNotAllowedException;
use VoltStack\Framework\Application;

final class Router
{
    private RouteCollection $routes;
    private RouteMatcher $matcher;
    /**
     * @var array<int, array{prefix: string, domain: ?string, middleware: array<int, mixed>, metadata: array<string, mixed>}>
     */
    private array $groupStack = [];

    public function __construct(private readonly Application $app)
    {
        $this->routes = new RouteCollection();
        $this->matcher = new RouteMatcher();
    }

    public function get(string $uri, mixed $action): Route
    {
        return $this->addRoute(['GET'], $uri, $action);
    }

    public function post(string $uri, mixed $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    public function put(string $uri, mixed $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    public function patch(string $uri, mixed $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    public function delete(string $uri, mixed $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    public function head(string $uri, mixed $action): Route
    {
        return $this->addRoute(['HEAD'], $uri, $action);
    }

    public function options(string $uri, mixed $action): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    /**
     * @param array<int, string> $methods
     */
    public function match(array $methods, string $uri, mixed $action): Route
    {
        return $this->addRoute($methods, $uri, $action);
    }

    public function any(string $uri, mixed $action): Route
    {
        return $this->addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    public function aliasMiddleware(string $alias, mixed $middleware): void
    {
        $this->middlewareAliases()->alias($alias, $middleware);
    }

    public function group(array|callable $attributes, ?callable $callback = null): void
    {
        if (is_callable($attributes) && $callback === null) {
            $callback = $attributes;
            $attributes = [];
        }

        if (! is_array($attributes) || ! is_callable($callback)) {
            throw new \InvalidArgumentException('Router::group expects attributes and a callback.');
        }

        $this->groupStack[] = $this->mergeGroupAttributes($this->currentGroupAttributes(), $attributes);

        try {
            $callback($this);
        } finally {
            array_pop($this->groupStack);
        }
    }

    /**
     * @param array<int, string> $methods
     */
    public function addRoute(array $methods, string $uri, mixed $action): Route
    {
        $groupAttributes = $this->currentGroupAttributes();
        $route = new Route(RouteDefinition::make(
            $methods,
            $this->mergeGroupPrefix($groupAttributes['prefix'], $uri),
            $action,
        ));
        $route->attachMiddlewareResolver(fn(mixed $middleware): mixed => $this->middlewareAliases()->resolve($middleware));

        if ($groupAttributes['domain'] !== null) {
            $route->domain($groupAttributes['domain']);
        }

        if ($groupAttributes['middleware'] !== []) {
            $route->middleware($groupAttributes['middleware']);
        }

        if ($groupAttributes['metadata'] !== []) {
            $route->meta($groupAttributes['metadata']);
        }

        return $this->routes->add($route);
    }

    /**
     * @return array<int, Route>
     */
    public function routes(): array
    {
        return $this->routes->all();
    }

    public function collection(): RouteCollection
    {
        return $this->routes;
    }

    public function dispatch(Request $request): mixed
    {
        try {
            $match = $this->matcher->match($request, $this->routes);
        } catch (MethodNotAllowedException $exception) {
            if ($request->method() === 'OPTIONS') {
                return new Response('', 204, [
                    'Allow' => $exception->allowHeader(),
                ]);
            }

            throw $exception;
        }

        $request->setRouteParameters($match->parameters());
        $request->setRouteMetadata($match->metadata()->all());

        return $match->route()->routePipeline()->handle(
            $this->app,
            $request,
            fn(Request $request): mixed => $this->app->make(DispatcherResolver::class)->dispatch($match, $request),
        );
    }

    /**
     * @return array{prefix: string, domain: ?string, middleware: array<int, mixed>, metadata: array<string, mixed>}
     */
    private function currentGroupAttributes(): array
    {
        return $this->groupStack === []
            ? ['prefix' => '', 'domain' => null, 'middleware' => [], 'metadata' => []]
            : $this->groupStack[array_key_last($this->groupStack)];
    }

    /**
     * @param array{prefix: string, domain: ?string, middleware: array<int, mixed>, metadata: array<string, mixed>} $parent
     * @param array<string, mixed> $attributes
     * @return array{prefix: string, domain: ?string, middleware: array<int, mixed>, metadata: array<string, mixed>}
     */
    private function mergeGroupAttributes(array $parent, array $attributes): array
    {
        $prefix = $parent['prefix'];

        if (isset($attributes['prefix']) && is_string($attributes['prefix'])) {
            $prefix = $this->mergeGroupPrefix($prefix, $attributes['prefix']);
        }

        $domain = $parent['domain'];

        if (array_key_exists('domain', $attributes)) {
            $domain = is_string($attributes['domain']) && $attributes['domain'] !== ''
                ? $attributes['domain']
                : null;
        }

        $middlewares = $parent['middleware'];

        if (array_key_exists('middleware', $attributes)) {
            $resolvedMiddlewares = $this->middlewareAliases()->resolveMany(
                is_array($attributes['middleware']) ? array_values($attributes['middleware']) : [$attributes['middleware']]
            );

            $middlewares = MiddlewareStack::deduplicate([
                ...$middlewares,
                ...$resolvedMiddlewares,
            ]);
        }

        $metadata = $parent['metadata'];

        if (array_key_exists('metadata', $attributes) && is_array($attributes['metadata'])) {
            $metadata = array_replace($metadata, $attributes['metadata']);
        }

        return [
            'prefix' => $prefix,
            'domain' => $domain,
            'middleware' => $middlewares,
            'metadata' => $metadata,
        ];
    }

    private function mergeGroupPrefix(string $prefix, string $uri): string
    {
        $normalizedPrefix = trim($prefix, '/');
        $normalizedUri = trim($uri, '/');

        if ($normalizedPrefix === '') {
            return $normalizedUri === '' ? '/' : $normalizedUri;
        }

        if ($normalizedUri === '') {
            return $normalizedPrefix;
        }

        return $normalizedPrefix . '/' . $normalizedUri;
    }

    private function middlewareAliases(): MiddlewareAliasRegistry
    {
        return $this->app->make(MiddlewareAliasRegistry::class);
    }
}
