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
        private array $resourceParameters,
        private readonly string $resource,
        private readonly string $resourceSegment,
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
        return $this->renameResourceParameter($this->resource, $parameter);
    }

    /**
     * @param array<string, string> $parameters
     */
    public function parameters(array $parameters): self
    {
        $applied = false;

        foreach ($this->resourceParameters as $resourceKey => $parameterName) {
            if (array_key_exists($resourceKey, $parameters)) {
                $this->renameResourceParameter($resourceKey, (string) $parameters[$resourceKey]);
                $applied = true;
                continue;
            }

            if (array_key_exists($parameterName, $parameters)) {
                $this->renameResourceParameter($resourceKey, (string) $parameters[$parameterName]);
                $applied = true;
            }
        }

        if ($applied) {
            return $this;
        }

        throw new \InvalidArgumentException(sprintf(
            'Resource parameters must define at least one of [%s].',
            implode(', ', $this->availableParameterKeys()),
        ));
    }

    public function shallow(): self
    {
        $parameter = $this->resourceParameters[$this->resource] ?? null;

        if (! is_string($parameter) || $parameter === '') {
            return $this;
        }

        $memberBasePath = $this->resourceSegment . '/{' . $parameter . '}';

        foreach (
            [
                'show' => $memberBasePath,
                'edit' => $memberBasePath . '/edit',
                'update' => $memberBasePath,
                'destroy' => $memberBasePath,
            ] as $action => $uri
        ) {
            $route = $this->routes[$action] ?? null;

            if (! $route instanceof Route) {
                continue;
            }

            $route->repath($uri);
        }

        return $this;
    }

    public function missing(string|int $strategy, int $status = 302): self
    {
        $payload = is_int($strategy)
            ? $this->missingStatusPayload($strategy)
            : $this->missingRedirectPayload($strategy, $status);

        foreach (['show', 'edit', 'update', 'destroy'] as $action) {
            $route = $this->routes[$action] ?? null;

            if (! $route instanceof Route) {
                continue;
            }

            $route->meta('missing', $payload);
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

        $parameter = $this->resourceParameters[$this->resource] ?? null;

        if ($action === 'create' && is_string($parameter) && $parameter !== '') {
            $this->reserveParameterLiteral('create', $parameter);
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

    private function renameResourceParameter(string $resourceKey, string $parameter): self
    {
        $normalizedKey = $this->normalizeActionKey($resourceKey);
        $currentParameter = $this->resourceParameters[$normalizedKey] ?? null;

        if (! is_string($currentParameter) || $currentParameter === '') {
            throw new \InvalidArgumentException(sprintf(
                'Resource parameters must define a known resource key. Available keys are [%s].',
                implode(', ', array_keys($this->resourceParameters)),
            ));
        }

        $normalizedParameter = $this->normalizeParameterName($parameter);

        if ($normalizedParameter === $currentParameter) {
            return $this;
        }

        foreach ($this->routes as $route) {
            if (! in_array($currentParameter, $route->parameterNames(), true)) {
                continue;
            }

            $route->renameParameter($currentParameter, $normalizedParameter);
        }

        if ($normalizedKey === $this->resource && ! isset($this->routes['create'])) {
            $this->reserveParameterLiteral('create', $normalizedParameter);
        }

        $this->resourceParameters[$normalizedKey] = $normalizedParameter;

        return $this;
    }

    /**
     * @return array{type: string, status: int}
     */
    private function missingStatusPayload(int $status): array
    {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException(sprintf(
                'Missing resource status [%d] is invalid.',
                $status,
            ));
        }

        return [
            'type' => 'status',
            'status' => $status,
        ];
    }

    /**
     * @return array{type: string, name: string, status: int}
     */
    private function missingRedirectPayload(string $routeName, int $status): array
    {
        $normalizedRoute = trim($routeName);

        if ($normalizedRoute === '') {
            throw new \InvalidArgumentException('Missing resource route name cannot be empty.');
        }

        if ($status < 300 || $status > 399) {
            throw new \InvalidArgumentException(sprintf(
                'Missing resource redirect status [%d] is invalid.',
                $status,
            ));
        }

        return [
            'type' => 'route',
            'name' => $normalizedRoute,
            'status' => $status,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function availableParameterKeys(): array
    {
        return array_values(array_unique([
            ...array_keys($this->resourceParameters),
            ...array_values($this->resourceParameters),
        ]));
    }
}
