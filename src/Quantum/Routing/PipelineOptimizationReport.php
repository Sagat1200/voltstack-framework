<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class PipelineOptimizationReport
{
    /**
     * @param array<int, string> $warnings
     */
    public function __construct(
        private readonly int $totalRoutes,
        private readonly int $uniquePipelines,
        private readonly int $sharedRouteCount,
        private readonly ?string $longestRouteUri,
        private readonly int $longestPipelineLength,
        private readonly array $warnings,
    ) {}

    public function totalRoutes(): int
    {
        return $this->totalRoutes;
    }

    public function uniquePipelines(): int
    {
        return $this->uniquePipelines;
    }

    public function sharedRouteCount(): int
    {
        return $this->sharedRouteCount;
    }

    public function longestRouteUri(): ?string
    {
        return $this->longestRouteUri;
    }

    public function longestPipelineLength(): int
    {
        return $this->longestPipelineLength;
    }

    /**
     * @return array<int, string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }
}

