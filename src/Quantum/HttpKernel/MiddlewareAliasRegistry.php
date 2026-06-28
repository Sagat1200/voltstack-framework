<?php

declare(strict_types=1);

namespace Quantum\HttpKernel;

use RuntimeException;

final class MiddlewareAliasRegistry
{
    /**
     * @var array<string, mixed>
     */
    private array $aliases = [];

    public function alias(string $alias, mixed $middleware): void
    {
        $normalized = trim($alias);

        if ($normalized === '') {
            throw new RuntimeException('Middleware alias cannot be empty.');
        }

        $this->aliases[$normalized] = $middleware;
    }

    /**
     * @param array<int, mixed> $middlewares
     * @return array<int, mixed>
     */
    public function resolveMany(array $middlewares): array
    {
        return array_map(fn(mixed $middleware): mixed => $this->resolve($middleware), array_values($middlewares));
    }

    public function resolve(mixed $middleware): mixed
    {
        if (! is_string($middleware)) {
            return $middleware;
        }

        $normalized = trim($middleware);

        if ($normalized === '') {
            throw new RuntimeException('Middleware alias cannot be empty.');
        }

        $resolved = $this->aliases[$normalized] ?? $normalized;

        if (is_string($resolved) && ! class_exists($resolved)) {
            throw new RuntimeException(sprintf(
                'Middleware [%s] is not a registered alias or valid class.',
                $normalized,
            ));
        }

        return $resolved;
    }
}