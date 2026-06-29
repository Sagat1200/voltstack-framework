<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class RouteMatchTree
{
    /**
     * @param array<string, array<int, int>> $staticRoutes
     * @param array<string, array<int, array<int, int>>> $dynamicRoutes
     */
    public function __construct(
        private readonly int $routeCount,
        private readonly array $staticRoutes,
        private readonly array $dynamicRoutes,
    ) {}

    public function routeCount(): int
    {
        return $this->routeCount;
    }

    /**
     * @return array<int, int>
     */
    public function candidatesFor(string $path): array
    {
        $normalizedPath = $this->normalizePath($path);
        $segments = $this->segments($normalizedPath);
        $segmentCount = count($segments);
        $firstSegment = $segments[0] ?? '';
        $candidateIndices = $this->staticRoutes[$normalizedPath] ?? [];

        foreach ($this->dynamicRoutes[$firstSegment][$segmentCount] ?? [] as $routeIndex) {
            $candidateIndices[] = $routeIndex;
        }

        if ($firstSegment !== '*' && isset($this->dynamicRoutes['*'][$segmentCount])) {
            foreach ($this->dynamicRoutes['*'][$segmentCount] as $routeIndex) {
                $candidateIndices[] = $routeIndex;
            }
        }

        $candidateIndices = array_values(array_unique($candidateIndices));
        sort($candidateIndices);

        return $candidateIndices;
    }

    private function normalizePath(string $path): string
    {
        $trimmed = trim($path, '/');

        return $trimmed === '' ? '/' : '/' . $trimmed;
    }

    /**
     * @return array<int, string>
     */
    private function segments(string $path): array
    {
        $trimmed = trim($path, '/');

        if ($trimmed === '') {
            return [];
        }

        return explode('/', $trimmed);
    }
}
