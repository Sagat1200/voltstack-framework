<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, CompiledRoute>
 */
final class CompiledRouteCollection implements Countable, IteratorAggregate
{
    /**
     * @param array<int, CompiledRoute> $routes
     */
    public function __construct(
        private readonly array $routes,
    ) {}

    /**
     * @return array<int, CompiledRoute>
     */
    public function all(): array
    {
        return $this->routes;
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function at(int $index): ?CompiledRoute
    {
        return $this->routes[$index] ?? null;
    }

    public function getIterator(): Traversable
    {
        yield from $this->routes;
    }

    public function named(string $name): ?CompiledRoute
    {
        $normalized = trim($name);

        if ($normalized === '') {
            return null;
        }

        foreach ($this->routes as $route) {
            if ($route->routeName() === $normalized) {
                return $route;
            }
        }

        return null;
    }
}
