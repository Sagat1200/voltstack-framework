<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\Routing\Exceptions\MethodNotAllowedException;
use Quantum\Routing\Exceptions\RouteNotFoundException;
use VoltStack\Framework\Application;

final class Router
{
    private RouteCollection $routes;

    public function __construct(private readonly Application $app)
    {
        $this->routes = new RouteCollection();
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

    /**
     * @param array<int, string> $methods
     */
    public function addRoute(array $methods, string $uri, mixed $action): Route
    {
        $route = new Route(RouteDefinition::make($methods, $uri, $action));

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
        $path = $request->path();
        $method = $request->method();
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $parameters = $route->matches($request);

            if ($parameters === null) {
                $pathParameters = $route->matchPath($path);

                if ($pathParameters === null) {
                    continue;
                }

                $allowedMethods = [...$allowedMethods, ...$route->methods()];
                continue;
            }

            $request->setRouteParameters($parameters);

            return $route->run($this->app, $request);
        }

        if ($method === 'HEAD') {
            foreach ($this->routes as $route) {
                if (! $route->allowsMethod('GET')) {
                    continue;
                }

                $parameters = $route->matchPath($path);

                if ($parameters === null) {
                    continue;
                }

                $request->setRouteParameters($parameters);

                return $route->run($this->app, $request);
            }
        }

        if ($allowedMethods !== []) {
            $allowedMethods = $this->normalizeAllowedMethods($allowedMethods);

            if ($method === 'OPTIONS') {
                return new Response('', 204, [
                    'Allow' => implode(', ', $allowedMethods),
                ]);
            }

            throw new MethodNotAllowedException($method, $allowedMethods, $path);
        }

        throw new RouteNotFoundException(sprintf(
            'No route matched [%s] %s.',
            $method,
            $path,
        ));
    }

    /**
     * @param array<int, string> $methods
     * @return array<int, string>
     */
    private function normalizeAllowedMethods(array $methods): array
    {
        $methods = array_map('strtoupper', $methods);

        if (in_array('GET', $methods, true)) {
            $methods[] = 'HEAD';
        }

        $methods[] = 'OPTIONS';
        $methods = array_values(array_unique($methods));
        $priority = array_flip(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']);

        usort($methods, static function (string $left, string $right) use ($priority): int {
            return ($priority[$left] ?? PHP_INT_MAX) <=> ($priority[$right] ?? PHP_INT_MAX);
        });

        return $methods;
    }
}
