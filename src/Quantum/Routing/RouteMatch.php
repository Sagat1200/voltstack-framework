<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class RouteMatch
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        private readonly Route $route,
        private readonly array $parameters,
        private readonly string $resolvedMethod,
        private readonly bool $usedHeadFallback = false,
    ) {}

    public function route(): Route
    {
        return $this->route;
    }

    /**
     * @return array<string, string>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    public function resolvedMethod(): string
    {
        return $this->resolvedMethod;
    }

    public function usedHeadFallback(): bool
    {
        return $this->usedHeadFallback;
    }
}
