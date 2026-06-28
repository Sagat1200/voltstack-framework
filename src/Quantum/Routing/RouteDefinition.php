<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class RouteDefinition
{
    /**
     * @param array<int, string> $methods
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $uri,
        private readonly mixed $action,
    ) {}

    /**
     * @param array<int, string> $methods
     */
    public static function make(array $methods, string $uri, mixed $action): self
    {
        $normalizedMethods = array_values(array_unique(array_map(static fn(string $method): string => strtoupper($method), $methods)));

        return new self(
            $normalizedMethods,
            self::normalizeUri($uri),
            $action,
        );
    }

    /**
     * @return array<int, string>
     */
    public function methods(): array
    {
        return $this->methods;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function action(): mixed
    {
        return $this->action;
    }

    private static function normalizeUri(string $uri): string
    {
        if ($uri === '') {
            return '/';
        }

        $normalized = '/' . trim($uri, '/');

        return $normalized === '//' ? '/' : $normalized;
    }
}
