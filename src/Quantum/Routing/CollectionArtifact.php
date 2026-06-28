<?php

declare(strict_types=1);

namespace Quantum\Routing;

use RuntimeException;

final class CollectionArtifact
{
    /**
     * @param array<int, array{
     *     methods: array<int, string>,
     *     uri: string,
     *     domain: ?string,
     *     action: array{kind: string, value: string|array<int, string>},
     *     name: ?string,
     *     constraints: array<string, string>,
     *     middlewares: array<int, string>,
     *     metadata: array<string, mixed>
     * }> $routes
     */
    public function __construct(
        private readonly int $version,
        private readonly array $routes,
    ) {}

    public static function fromArray(array $payload): self
    {
        $version = $payload['version'] ?? null;
        $routes = $payload['routes'] ?? null;

        if (! is_int($version) || ! is_array($routes)) {
            throw new RuntimeException('Collection artifact payload is invalid.');
        }

        return new self($version, array_values($routes));
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * @return array<int, array{
     *     methods: array<int, string>,
     *     uri: string,
     *     domain: ?string,
     *     action: array{kind: string, value: string|array<int, string>},
     *     name: ?string,
     *     constraints: array<string, string>,
     *     middlewares: array<int, string>,
     *     metadata: array<string, mixed>
     * }>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    public function compileCollection(): CompiledRouteCollection
    {
        $compiledRoutes = [];

        foreach ($this->routes as $route) {
            $compiledRoutes[] = new CompiledRoute(new RouteDefinition(
                $route['methods'],
                $route['uri'],
                $route['domain'],
                $this->restoreAction($route['action']),
                $route['name'],
                $route['constraints'],
                $route['middlewares'],
                $route['metadata'],
            ));
        }

        return new CompiledRouteCollection($compiledRoutes);
    }

    /**
     * @return array{version: int, routes: array<int, array{
     *     methods: array<int, string>,
     *     uri: string,
     *     domain: ?string,
     *     action: array{kind: string, value: string|array<int, string>},
     *     name: ?string,
     *     constraints: array<string, string>,
     *     middlewares: array<int, string>,
     *     metadata: array<string, mixed>
     * }>}
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'routes' => $this->routes,
        ];
    }

    private function restoreAction(array $action): mixed
    {
        $kind = $action['kind'] ?? null;
        $value = $action['value'] ?? null;

        if ($kind === 'string' && is_string($value)) {
            return $value;
        }

        if ($kind === 'controller' && is_array($value) && count($value) === 2) {
            return [(string) $value[0], (string) $value[1]];
        }

        throw new RuntimeException('Collection artifact route action is invalid.');
    }
}
