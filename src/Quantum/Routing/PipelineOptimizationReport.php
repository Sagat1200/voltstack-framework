<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class PipelineOptimizationReport
{
    public function __construct(
        private readonly int $totalRoutes,
        private readonly int $uniquePipelines,
        private readonly int $sharedRouteCount,
        private readonly int $singletonPipelines,
        private readonly int $maxPipelineReuse,
        private readonly array $topReusedPipelines,
        private readonly array $singletonRouteExamples,
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

    public function singletonPipelines(): int
    {
        return $this->singletonPipelines;
    }

    public function maxPipelineReuse(): int
    {
        return $this->maxPipelineReuse;
    }

    public function topReusedPipelines(): array
    {
        return $this->topReusedPipelines;
    }

    public function singletonRouteExamples(): array
    {
        return $this->singletonRouteExamples;
    }

    public function longestRouteUri(): ?string
    {
        return $this->longestRouteUri;
    }

    public function longestPipelineLength(): int
    {
        return $this->longestPipelineLength;
    }

    public function warnings(): array
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }
}