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
        private readonly string $resource,
        private string $parameter,
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
     * @param array<string, string> $names
     */
    public function names(array $names): self
    {
        foreach ($names as $action => $name) {
            $normalizedAction = $this->normalizeActionKey($action);
            $route = $this->routes[$normalizedAction] ?? null;

            if (! $route instanceof Route) {
                throw new \InvalidArgumentException(sprintf(
                    'Resource action [%s] is not supported. Supported actions are [%s].',
                    $action,
                    implode(', ', array_keys($this->routes)),
                ));
            }

            $route->name($name);
        }

        return $this;
    }

    public function parameter(string $parameter): self
    {
        $normalizedParameter = $this->normalizeParameterName($parameter);

        if ($normalizedParameter === $this->parameter) {
            return $this;
        }

        foreach (['show', 'edit', 'update', 'destroy'] as $action) {
            $route = $this->routes[$action] ?? null;

            if (! $route instanceof Route) {
                continue;
            }

            $route->renameParameter($this->parameter, $normalizedParameter);
        }

        if (! isset($this->routes['create'])) {
            $this->reserveParameterLiteral('create', $normalizedParameter);
        }

        $this->parameter = $normalizedParameter;

        return $this;
    }

    /**
     * @param array<string, string> $parameters
     */
    public function parameters(array $parameters): self
    {
        $resourceKey = $this->normalizeActionKey($this->resource);

        if (array_key_exists($resourceKey, $parameters)) {
            return $this->parameter((string) $parameters[$resourceKey]);
        }

        if (array_key_exists($this->parameter, $parameters)) {
            return $this->parameter((string) $parameters[$this->parameter]);
        }

        throw new \InvalidArgumentException(sprintf(
            'Resource parameters must define [%s] or [%s].',
            $resourceKey,
            $this->parameter,
        ));
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
            $candidate = $this->normalizeActionKey($action);

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
            $this->reserveParameterLiteral('create', $this->parameter);
        }
    }

    private function reserveParameterLiteral(string $literal, string $parameter): void
    {
        foreach (['show', 'update', 'destroy'] as $action) {
            $route = $this->routes[$action] ?? null;

            if (! $route instanceof Route) {
                continue;
            }

            $route->where($parameter, sprintf('(?!(?:%s)$)[^\/]+', preg_quote($literal, '/')));
        }
    }

    private function normalizeActionKey(string $action): string
    {
        return strtolower(trim($action));
    }

    private function normalizeParameterName(string $parameter): string
    {
        $normalized = trim($parameter);

        if ($normalized === '') {
            throw new \InvalidArgumentException('Resource parameter name cannot be empty.');
        }

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $normalized) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                'Resource parameter name [%s] is invalid.',
                $parameter,
            ));
        }

        return $normalized;
    }
}
