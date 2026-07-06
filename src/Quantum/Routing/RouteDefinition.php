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
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $uri,
        private readonly ?string $domain,
        private readonly mixed $action,
        private readonly ?string $name = null,
        private readonly array $constraints = [],
        private readonly array $middlewares = [],
        private readonly array $metadata = [],
    ) {}

    /**
     * @param array<int, string> $methods
     */
    public static function make(array $methods, string $uri, mixed $action): self
    {
        $normalizedMethods = self::normalizeMethods($methods);

        return new self(
            $normalizedMethods,
            self::normalizeUri($uri),
            null,
            $action,
            null,
            [],
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

    public function path(): string
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

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
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
            $this->metadata,
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
            $this->metadata,
        );
    }

    public function withPath(string $uri): self
    {
        $normalizedUri = self::normalizeUri($uri);

        if ($normalizedUri === $this->uri) {
            return $this;
        }

        $parameters = $this->parameterNamesFor($normalizedUri, $this->domain);
        $constraints = array_intersect_key($this->constraints, array_flip($parameters));
        $metadata = $this->metadata;
        $aliases = $this->parameterAliases();

        foreach ($aliases as $alias => $target) {
            if (! in_array($target, $parameters, true) || $alias === $target) {
                unset($aliases[$alias]);
            }
        }

        if ($aliases === []) {
            unset($metadata['parameter_aliases']);
        } else {
            $metadata['parameter_aliases'] = $aliases;
        }

        return new self(
            $this->methods,
            $normalizedUri,
            $this->domain,
            $this->action,
            $this->name,
            $constraints,
            $this->middlewares,
            $metadata,
        );
    }

    /**
     * @param array<int, string> $methods
     */
    public function withMethods(array $methods): self
    {
        $normalizedMethods = self::normalizeMethods($methods);

        if ($normalizedMethods === $this->methods) {
            return $this;
        }

        return new self(
            $normalizedMethods,
            $this->uri,
            $this->domain,
            $this->action,
            $this->name,
            $this->constraints,
            $this->middlewares,
            $this->metadata,
        );
    }

    public function renameParameter(string $from, string $to): self
    {
        $normalizedFrom = trim($from);
        $normalizedTo = trim($to);

        if ($normalizedFrom === '' || $normalizedTo === '') {
            throw new \InvalidArgumentException('Route parameter names cannot be empty.');
        }

        if ($normalizedFrom === $normalizedTo) {
            return $this;
        }

        if (! in_array($normalizedFrom, $this->parameterNames(), true)) {
            throw new \InvalidArgumentException(sprintf(
                'Route parameter [%s] is not defined for route [%s].',
                $normalizedFrom,
                $this->uri,
            ));
        }

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $normalizedTo) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                'Route parameter name [%s] is invalid.',
                $normalizedTo,
            ));
        }

        if (in_array($normalizedTo, $this->parameterNames(), true)) {
            throw new \InvalidArgumentException(sprintf(
                'Route parameter [%s] already exists on route [%s].',
                $normalizedTo,
                $this->uri,
            ));
        }

        $constraints = [];

        foreach ($this->constraints as $parameter => $pattern) {
            $constraints[$parameter === $normalizedFrom ? $normalizedTo : $parameter] = $pattern;
        }

        $metadata = $this->metadata;
        $aliases = $this->parameterAliases();

        foreach ($aliases as $alias => $target) {
            if ($target === $normalizedFrom) {
                $aliases[$alias] = $normalizedTo;
            }
        }

        $aliases[$normalizedFrom] = $normalizedTo;

        foreach ($aliases as $alias => $target) {
            if ($alias === $target) {
                unset($aliases[$alias]);
            }
        }

        if ($aliases === []) {
            unset($metadata['parameter_aliases']);
        } else {
            $metadata['parameter_aliases'] = $aliases;
        }

        return new self(
            $this->methods,
            $this->renamePlaceholder($this->uri, $normalizedFrom, $normalizedTo),
            $this->domain === null ? null : $this->renamePlaceholder($this->domain, $normalizedFrom, $normalizedTo),
            $this->action,
            $this->name,
            $constraints,
            $this->middlewares,
            $metadata,
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
            $this->metadata,
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
            $this->metadata,
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
            $this->metadata,
        );
    }

    public function withMetadata(string $key, mixed $value): self
    {
        $normalizedKey = trim($key);

        if ($normalizedKey === '') {
            throw new \InvalidArgumentException('Route metadata key cannot be empty.');
        }

        return new self(
            $this->methods,
            $this->uri,
            $this->domain,
            $this->action,
            $this->name,
            $this->constraints,
            $this->middlewares,
            [
                ...$this->metadata,
                $normalizedKey => $value,
            ],
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function withMetadataBag(array $metadata): self
    {
        return new self(
            $this->methods,
            $this->uri,
            $this->domain,
            $this->action,
            $this->name,
            $this->constraints,
            $this->middlewares,
            array_replace($this->metadata, $metadata),
        );
    }

    /**
     * @return array<int, string>
     */
    private function parameterNames(): array
    {
        return $this->parameterNamesFor($this->uri, $this->domain);
    }

    /**
     * @return array<string, string>
     */
    private function parameterAliases(): array
    {
        $aliases = $this->metadata['parameter_aliases'] ?? null;

        if (! is_array($aliases)) {
            return [];
        }

        $normalized = [];

        foreach ($aliases as $alias => $target) {
            if (! is_string($alias) || ! is_string($target)) {
                continue;
            }

            $normalized[trim($alias)] = trim($target);
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function parameterNamesFor(string $uri, ?string $domain): array
    {
        preg_match_all('/\{([^}]+)\}/', $uri, $parameterMatches);
        $pathParameters = $parameterMatches[1];

        preg_match_all('/\{([^}]+)\}/', $domain ?? '', $domainParameterMatches);
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

    /**
     * @param array<int, string> $methods
     * @return array<int, string>
     */
    private static function normalizeMethods(array $methods): array
    {
        $normalized = [];

        foreach ($methods as $method) {
            $candidate = strtoupper(trim($method));

            if ($candidate === '') {
                continue;
            }

            $normalized[] = $candidate;
        }

        $normalized = array_values(array_unique($normalized));

        if ($normalized === []) {
            throw new \InvalidArgumentException('Route methods cannot be empty.');
        }

        return $normalized;
    }

    private function renamePlaceholder(string $template, string $from, string $to): string
    {
        return str_replace('{' . $from . '}', '{' . $to . '}', $template);
    }
}
