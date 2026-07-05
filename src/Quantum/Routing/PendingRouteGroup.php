<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class PendingRouteGroup
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private readonly Router $router,
        private array $attributes = [],
    ) {}

    public function prefix(string $prefix): self
    {
        $this->attributes['prefix'] = $prefix;

        return $this;
    }

    public function name(string $name): self
    {
        $this->attributes['name'] = $name;

        return $this;
    }

    public function domain(string $domain): self
    {
        $this->attributes['domain'] = $domain;

        return $this;
    }

    public function middleware(mixed $middleware): self
    {
        $this->attributes['middleware'] = $middleware;

        return $this;
    }

    public function metadata(array $metadata): self
    {
        $this->attributes['metadata'] = $metadata;

        return $this;
    }

    public function meta(array $metadata): self
    {
        return $this->metadata($metadata);
    }

    public function group(callable $callback): void
    {
        $this->router->group($this->attributes, $callback);
    }
}
