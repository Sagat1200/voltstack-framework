<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, Route>
 */
final class PendingResourceRegistration implements Countable, IteratorAggregate
{
    /**
     * @var array<string, Route>
     */
    private array $routes;

    /**
     * @param array<string, Route> $routes
     */
    public function __construct(
        private readonly RouteCollection $collection,
        private readonly string $parameter,
        array $routes,
    ) {
        $this->routes = $routes;
    }

    /**
     * @param array<int, string> $actions
     */
    public function only(array $actions): self
    {
        $allowed = $this->normalizeActions($actions);

        foreach (array_keys($this->routes) as $action) {
            if (in_array($action, $allowed, true)) {
                continue;
            }

            $this->removeAction($action);
        }

        return $this;
    }

    /**
     * @param array<int, string> $actions
     */
    public function except(array $actions): self
    {
        foreach ($this->normalizeActions($actions) as $action) {
            $this->removeAction($action);
        }

        return $this;
    }

    /**
     * @return array<int, Route>
     */
    public function all(): array
    {
        return array_values($this->routes);
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function getIterator(): Traversable
    {
        yield from $this->all();
    }

    /**
     * @param array<int, string> $actions
     * @return array<int, string>
     */
    private function normalizeActions(array $actions): array
    {
        $normalized = [];

        foreach ($actions as $action) {
            $candidate = strtolower(trim($action));

            if ($candidate === '') {
                continue;
            }

            if (! array_key_exists($candidate, $this->routes)) {
                throw new \InvalidArgumentException(sprintf(
                    'Resource action [%s] is not supported. Supported actions are [%s].',
                    $action,
                    implode(', ', array_keys($this->routes)),
                ));
            }

            $normalized[] = $candidate;
        }

        return array_values(array_unique($normalized));
    }

    private function removeAction(string $action): void
    {
        $route = $this->routes[$action] ?? null;

        if ($route === null) {
            return;
        }

        $this->collection->remove($route);
        unset($this->routes[$action]);

        if ($action === 'create') {
            $this->reserveParameterLiteral('create');
        }
    }

    private function reserveParameterLiteral(string $literal): void
    {
        foreach (['show', 'update', 'destroy'] as $action) {
            $route = $this->routes[$action] ?? null;

            if (! $route instanceof Route) {
                continue;
            }

            $route->where($this->parameter, sprintf('(?!(?:%s)$)[^\/]+', preg_quote($literal, '/')));
        }
    }
}
