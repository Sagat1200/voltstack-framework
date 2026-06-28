<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class RouteDefinition
{
    /**
     * @param array<int, string> $methods
     * @param array<string, string> $constraints
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $uri,
        private readonly mixed $action,
        private readonly ?string $name = null,
        private readonly array $constraints = [],
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
            null,
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

    public function withName(string $name): self
    {
        $normalizedName = trim($name);

        if ($normalizedName === '') {
            throw new \InvalidArgumentException('Route name cannot be empty.');
        }

        return new self(
            $this->methods,
            $this->uri,
            $this->action,
            $normalizedName,
            $this->constraints,
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
            $this->action,
            $this->name,
            [
                ...$this->constraints,
                $normalizedParameter => $normalizedPattern,
            ],
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

    /**
     * @return array<int, string>
     */
    private function parameterNames(): array
    {
        preg_match_all('/\{([^}]+)\}/', $this->uri, $parameterMatches);

        return $parameterMatches[1];
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
