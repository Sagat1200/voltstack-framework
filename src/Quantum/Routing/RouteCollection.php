<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Countable;
use IteratorAggregate;
use Quantum\Routing\Exceptions\DuplicateRouteException;
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

    private function signature(string $method, string $uri): string
    {
        return strtoupper($method) . ' ' . $uri;
    }
}
