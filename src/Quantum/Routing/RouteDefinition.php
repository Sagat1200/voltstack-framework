<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Quantum\HttpKernel\MiddlewareStack;

final class RouteDefinition
{
    /**
     * @param array<int, string> $methods
     * @param array<string, string> $constraints
     * @param array<int, mixed> $middlewares
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $uri,
        private readonly ?string $domain,
        private readonly mixed $action,
        private readonly ?string $name = null,
        private readonly array $constraints = [],
        private readonly array $middlewares = [],
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
            null,
            $action,
            null,
            [],
            [],
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

    public function domain(): ?string
    {
        return $this->domain;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function constraints(): array
    {
        return $this->constraints;
    }

    /**
     * @return array<int, mixed>
     */
    public function middlewares(): array
    {
        return $this->middlewares;
    }

    public function withName(string $name): self
    {
        $normalizedName = trim($name);

        if ($normalizedName === '') {
            throw new \InvalidArgumentException('Route name cannot be empty.');
        }

        return new self(
            $this->methods,
            $this->uri,
            $this->domain,
            $this->action,
            $normalizedName,
            $this->constraints,
            $this->middlewares,
        );
    }

    public function withDomain(string $domain): self
    {
        $normalizedDomain = self::normalizeDomain($domain);

        if ($normalizedDomain === '') {
            throw new \InvalidArgumentException('Route domain cannot be empty.');
        }

        return new self(
            $this->methods,
            $this->uri,
            $normalizedDomain,
            $this->action,
            $this->name,
            $this->constraints,
            $this->middlewares,
        );
    }

    public function withConstraint(string $parameter, string $pattern): self
    {
        $normalizedParameter = trim($parameter);
        $normalizedPattern = trim($pattern);

        if ($normalizedParameter === '') {
            throw new \InvalidArgumentException('Route constraint parameter cannot be empty.');
        }

        if ($normalizedPattern === '') {
            throw new \InvalidArgumentException(sprintf(
                'Route constraint pattern for [%s] cannot be empty.',
                $normalizedParameter,
            ));
        }

        if (! in_array($normalizedParameter, $this->parameterNames(), true)) {
            throw new \InvalidArgumentException(sprintf(
                'Route parameter [%s] is not defined for route [%s].',
                $normalizedParameter,
                $this->uri,
            ));
        }

        return new self(
            $this->methods,
            $this->uri,
            $this->domain,
            $this->action,
            $this->name,
            [
                ...$this->constraints,
                $normalizedParameter => $normalizedPattern,
            ],
            $this->middlewares,
        );
    }

    /**
     * @param array<string, string> $constraints
     */
    public function withConstraints(array $constraints): self
    {
        $definition = $this;

        foreach ($constraints as $parameter => $pattern) {
            $definition = $definition->withConstraint((string) $parameter, (string) $pattern);
        }

        return $definition;
    }

    public function withMiddleware(mixed $middleware): self
    {
        return new self(
            $this->methods,
            $this->uri,
            $this->domain,
            $this->action,
            $this->name,
            $this->constraints,
            MiddlewareStack::deduplicate([
                ...$this->middlewares,
                $middleware,
            ]),
        );
    }

    /**
     * @param array<int, mixed> $middlewares
     */
    public function withMiddlewares(array $middlewares): self
    {
        return new self(
            $this->methods,
            $this->uri,
            $this->domain,
            $this->action,
            $this->name,
            $this->constraints,
            MiddlewareStack::deduplicate([
                ...$this->middlewares,
                ...array_values($middlewares),
            ]),
        );
    }

    /**
     * @return array<int, string>
     */
    private function parameterNames(): array
    {
        preg_match_all('/\{([^}]+)\}/', $this->uri, $parameterMatches);
        $pathParameters = $parameterMatches[1];

        preg_match_all('/\{([^}]+)\}/', $this->domain ?? '', $domainParameterMatches);
        $domainParameters = $domainParameterMatches[1];

        return array_values(array_unique([...$pathParameters, ...$domainParameters]));
    }

    private static function normalizeUri(string $uri): string
    {
        if ($uri === '') {
            return '/';
        }

        $normalized = '/' . trim($uri, '/');

        return $normalized === '//' ? '/' : $normalized;
    }

    private static function normalizeDomain(string $domain): string
    {
        $normalized = strtolower(trim($domain));
        $normalized = preg_replace('#^https?://#', '', $normalized) ?? $normalized;
        $normalized = rtrim($normalized, '/');

        return explode(':', $normalized, 2)[0];
    }
}
