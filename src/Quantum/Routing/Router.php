<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Quantum\Http\Request;
use Quantum\Routing\Exceptions\RouteNotFoundException;
use VoltStack\Framework\Application;

final class Router
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private readonly Application $app)
    {
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

    /**
     * @param array<int, string> $methods
     */
    public function match(array $methods, string $uri, mixed $action): Route
    {
        return $this->addRoute($methods, $uri, $action);
    }

    public function any(string $uri, mixed $action): Route
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $uri, $action);
    }

    /**
     * @param array<int, string> $methods
     */
    public function addRoute(array $methods, string $uri, mixed $action): Route
    {
        $route = new Route($methods, $uri, $action);
        $this->routes[] = $route;

        return $route;
    }

    /**
     * @return array<int, Route>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    public function dispatch(Request $request): mixed
    {
        foreach ($this->routes as $route) {
            $parameters = $route->matches($request);

            if ($parameters === null) {
                continue;
            }

            $request->setRouteParameters($parameters);

            return $route->run($this->app, $request);
        }

        throw new RouteNotFoundException(sprintf(
            'No route matched [%s] %s.',
            $request->method(),
            $request->path(),
        ));
    }
}
