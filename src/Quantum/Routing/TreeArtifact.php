<?php

declare(strict_types=1);

namespace Quantum\Routing;

use RuntimeException;

final class TreeArtifact
{
    /**
     * @param array<string, array<int, int>> $staticRoutes
     * @param array<string, array<int, array<int, int>>> $dynamicRoutes
     */
    public function __construct(
        private readonly int $version,
        private readonly int $routeCount,
        private readonly array $staticRoutes,
        private readonly array $dynamicRoutes,
    ) {}

    public static function fromArray(array $payload): self
    {
        $version = $payload['version'] ?? null;
        $routeCount = $payload['routeCount'] ?? null;
        $staticRoutes = $payload['staticRoutes'] ?? null;
        $dynamicRoutes = $payload['dynamicRoutes'] ?? null;

        if (! is_int($version) || ! is_int($routeCount) || ! is_array($staticRoutes) || ! is_array($dynamicRoutes)) {
            throw new RuntimeException('Tree artifact payload is invalid.');
        }

        return new self($version, $routeCount, $staticRoutes, $dynamicRoutes);
    }

    public function version(): int
    {
        return $this->version;
    }

    public function routeCount(): int
    {
        return $this->routeCount;
    }

    /**
     * @return array<string, array<int, int>>
     */
    public function staticRoutes(): array
    {
        return $this->staticRoutes;
    }

    /**
     * @return array<string, array<int, array<int, int>>>
     */
    public function dynamicRoutes(): array
    {
        return $this->dynamicRoutes;
    }

    public function compileTree(): RouteMatchTree
    {
        return new RouteMatchTree(
            $this->routeCount,
            $this->staticRoutes,
            $this->dynamicRoutes,
        );
    }

    /**
     * @return array{
     *     version: int,
     *     routeCount: int,
     *     staticRoutes: array<string, array<int, int>>,
     *     dynamicRoutes: array<string, array<int, array<int, int>>>
     * }
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'routeCount' => $this->routeCount,
            'staticRoutes' => $this->staticRoutes,
            'dynamicRoutes' => $this->dynamicRoutes,
        ];
    }
}
