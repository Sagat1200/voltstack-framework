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
    private ?CompiledRouteCollection $compiledCollection = null;

    /**
     * @var array<string, int>
     */
    private array $signatures = [];

    /**
     * @var array<string, int>
     */
    private array $names = [];

    public function add(Route $route): Route
    {
        $routeIndex = count($this->routes);

        foreach ($route->methods() as $method) {
            $signature = $this->signature($method, $route->routeDomain(), $route->uri());

            if (isset($this->signatures[$signature]) && $this->signatures[$signature] !== $routeIndex) {
                throw new DuplicateRouteException($method, $route->uri());
            }
        }

        foreach ($route->methods() as $method) {
            $this->signatures[$this->signature($method, $route->routeDomain(), $route->uri())] = $routeIndex;
        }

        $this->routes[] = $route;
        $this->compiledCollection = null;
        $route->attachCollection($this);
        $this->syncRouteName($route, null);
        $this->syncRouteDomain($route, null);

        return $route;
    }

    public function remove(Route $route): void
    {
        $currentIndex = array_search($route, $this->routes, true);

        if ($currentIndex === false) {
            return;
        }

        array_splice($this->routes, $currentIndex, 1);
        $this->rebuildIndexes();
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

    public function compiled(): CompiledRouteCollection
    {
        $this->validateForCompilation();

        return $this->compiledCollection ??= new CompiledRouteCollection($this->routes);
    }

    public function validateForCompilation(): void
    {
        (new RouteCompilerValidator())->validateRoutes($this->routes);
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

    public function validateRoutePath(Route $route, string $uri): void
    {
        $currentIndex = array_search($route, $this->routes, true);

        foreach ($route->methods() as $method) {
            $signature = $this->signature($method, $route->routeDomain(), $uri);
            $owner = $this->signatures[$signature] ?? null;

            if ($owner !== null && $owner !== $currentIndex) {
                throw new DuplicateRouteException($method, $uri);
            }
        }
    }

    public function validateRouteDomain(Route $route, string $domain, ?string $previousDomain): void
    {
        $currentIndex = array_search($route, $this->routes, true);

        foreach ($route->methods() as $method) {
            $signature = $this->signature($method, strtolower($domain), $route->uri());
            $owner = $this->signatures[$signature] ?? null;

            if ($owner !== null && $owner !== $currentIndex) {
                throw new DuplicateRouteException($method, $route->uri());
            }
        }

        if ($previousDomain === null || $currentIndex === false) {
            return;
        }

        foreach ($route->methods() as $method) {
            $previousSignature = $this->signature($method, $previousDomain, $route->uri());

            if (($this->signatures[$previousSignature] ?? null) === $currentIndex) {
                unset($this->signatures[$previousSignature]);
            }
        }
    }

    public function syncRouteDomain(Route $route, ?string $previousDomain): void
    {
        $currentIndex = array_search($route, $this->routes, true);

        if ($currentIndex === false) {
            return;
        }

        if ($previousDomain !== null) {
            foreach ($route->methods() as $method) {
                $previousSignature = $this->signature($method, $previousDomain, $route->uri());

                if (($this->signatures[$previousSignature] ?? null) === $currentIndex) {
                    unset($this->signatures[$previousSignature]);
                }
            }
        }

        foreach ($route->methods() as $method) {
            $this->signatures[$this->signature($method, $route->routeDomain(), $route->uri())] = $currentIndex;
        }
    }

    public function syncRoutePath(Route $route, ?string $previousUri): void
    {
        $currentIndex = array_search($route, $this->routes, true);

        if ($currentIndex === false) {
            return;
        }

        if ($previousUri !== null) {
            foreach ($route->methods() as $method) {
                $previousSignature = $this->signature($method, $route->routeDomain(), $previousUri);

                if (($this->signatures[$previousSignature] ?? null) === $currentIndex) {
                    unset($this->signatures[$previousSignature]);
                }
            }
        }

        foreach ($route->methods() as $method) {
            $this->signatures[$this->signature($method, $route->routeDomain(), $route->uri())] = $currentIndex;
        }
    }

    private function signature(string $method, ?string $domain, string $uri): string
    {
        return strtoupper($method) . ' ' . ($domain ?? '*') . ' ' . $uri;
    }

    private function rebuildIndexes(): void
    {
        $this->compiledCollection = null;
        $this->signatures = [];
        $this->names = [];

        foreach ($this->routes as $index => $route) {
            foreach ($route->methods() as $method) {
                $this->signatures[$this->signature($method, $route->routeDomain(), $route->uri())] = $index;
            }

            if ($route->routeName() !== null) {
                $this->names[$route->routeName()] = $index;
            }
        }
    }
}
