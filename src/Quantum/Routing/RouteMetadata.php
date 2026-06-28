<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class RouteMetadata
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(
        private readonly array $items,
    ) {}

    public static function fromDefinition(RouteDefinition $definition): self
    {
        return new self([
            'name' => $definition->name(),
            'methods' => $definition->methods(),
            'domain' => $definition->domain(),
            'middleware' => $definition->middlewares(),
            ...$definition->metadata(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }
}