<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\Routing\Dispatching\DispatcherResolver;
use Quantum\Routing\Exceptions\MethodNotAllowedException;
use VoltStack\Framework\Application;

final class Router
{
    private RouteCollection $routes;
    private RouteMatcher $matcher;

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

        return $this->app->make(DispatcherResolver::class)->dispatch($match, $request);
    }
}
