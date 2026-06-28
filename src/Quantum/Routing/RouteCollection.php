<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Countable;
use IteratorAggregate;
use Quantum\Routing\Exceptions\DuplicateRouteException;
use Quantum\Routing\Exceptions\DuplicateRouteNameException;
use Traversable;

/**
 * @implements IteratorAggregate<int, Route>
 */
final class RouteCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    /**
     * @var array<string, true>
     */
    private array $signatures = [];

    /**
     * @var array<string, int>
     */
    private array $names = [];

    public function add(Route $route): Route
    {
        foreach ($route->methods() as $method) {
            $signature = $this->signature($method, $route->uri());

            if (isset($this->signatures[$signature])) {
                throw new DuplicateRouteException($method, $route->uri());
            }
        }

        foreach ($route->methods() as $method) {
            $this->signatures[$this->signature($method, $route->uri())] = true;
        }

        $this->routes[] = $route;
        $route->attachCollection($this);
        $this->syncRouteName($route, null);

        return $route;
    }

    /**
     * @return array<int, Route>
     */
    public function all(): array
    {
        return $this->routes;
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function getIterator(): Traversable
    {
        yield from $this->routes;
    }

    public function named(string $name): ?Route
    {
        $routeIndex = $this->names[trim($name)] ?? null;

        if ($routeIndex === null) {
            return null;
        }

        return $this->routes[$routeIndex] ?? null;
    }

    public function validateRouteName(Route $route, string $name, ?string $previousName): void
    {
        $normalizedName = trim($name);

        if ($normalizedName === '') {
            throw new \InvalidArgumentException('Route name cannot be empty.');
        }

        $currentIndex = array_search($route, $this->routes, true);
        $owner = $this->names[$normalizedName] ?? null;

        if ($owner !== null && $owner !== $currentIndex) {
            throw new DuplicateRouteNameException($normalizedName);
        }

        if ($previousName !== null && $previousName !== $normalizedName && isset($this->names[$previousName]) && $this->names[$previousName] === $currentIndex) {
            unset($this->names[$previousName]);
        }
    }

    public function syncRouteName(Route $route, ?string $previousName): void
    {
        $name = $route->routeName();
        $currentIndex = array_search($route, $this->routes, true);

        if ($currentIndex === false) {
            return;
        }

        if ($previousName !== null && $previousName !== $name && isset($this->names[$previousName]) && $this->names[$previousName] === $currentIndex) {
            unset($this->names[$previousName]);
        }

        if ($name !== null) {
            $this->names[$name] = $currentIndex;
        }
    }

    private function signature(string $method, string $uri): string
    {
        return strtoupper($method) . ' ' . $uri;
    }
}
