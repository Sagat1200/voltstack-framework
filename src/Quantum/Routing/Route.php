<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class Route extends CompiledRoute
{
    private const UUID_PATTERN = '[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[1-5][0-9a-fA-F]{3}\-[89abAB][0-9a-fA-F]{3}\-[0-9a-fA-F]{12}';
    private const ALPHA_PATTERN = '[A-Za-z]+';
    private const ALPHA_NUMERIC_PATTERN = '[A-Za-z0-9]+';
    private const NUMBER_PATTERN = '[0-9]+';
    private const SLUG_PATTERN = '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*';

    private ?RouteCollection $collection = null;

    public function __construct(RouteDefinition $definition)
    {
        parent::__construct($definition);
    }

    public function attachCollection(RouteCollection $collection): void
    {
        $this->collection = $collection;
    }

    public function name(?string $name = null): string|static|null
    {
        if ($name === null) {
            return $this->routeName();
        }

        $previousName = $this->routeName();

        if ($this->collection !== null) {
            $this->collection->validateRouteName($this, $name, $previousName);
        }

        $this->replaceDefinition($this->definition()->withName($name));

        if ($this->collection !== null) {
            $this->collection->syncRouteName($this, $previousName);
        }

        return $this;
    }

    public function domain(?string $domain = null): string|static|null
    {
        if ($domain === null) {
            return $this->definition()->domain();
        }

        $previousDomain = $this->definition()->domain();

        if ($this->collection !== null) {
            $this->collection->validateRouteDomain($this, $domain, $previousDomain);
        }

        $this->replaceDefinition($this->definition()->withDomain($domain));

        if ($this->collection !== null) {
            $this->collection->syncRouteDomain($this, $previousDomain);
        }

        return $this;
    }

    public function where(string|array $parameter, ?string $pattern = null): static
    {
        if (is_array($parameter)) {
            $this->replaceDefinition($this->definition()->withConstraints($parameter));

            return $this;
        }

        if ($pattern === null) {
            throw new \InvalidArgumentException(sprintf(
                'Route constraint pattern for [%s] is required.',
                $parameter,
            ));
        }

        $this->replaceDefinition($this->definition()->withConstraint($parameter, $pattern));

        return $this;
    }

    public function whereNumber(string|array $parameter): static
    {
        return $this->applyNamedConstraint($parameter, self::NUMBER_PATTERN);
    }

    public function whereAlpha(string|array $parameter): static
    {
        return $this->applyNamedConstraint($parameter, self::ALPHA_PATTERN);
    }

    public function whereAlphaNumeric(string|array $parameter): static
    {
        return $this->applyNamedConstraint($parameter, self::ALPHA_NUMERIC_PATTERN);
    }

    public function whereUuid(string|array $parameter): static
    {
        return $this->applyNamedConstraint($parameter, self::UUID_PATTERN);
    }

    public function whereSlug(string|array $parameter): static
    {
        return $this->applyNamedConstraint($parameter, self::SLUG_PATTERN);
    }

    private function applyNamedConstraint(string|array $parameter, string $pattern): static
    {
        if (is_array($parameter)) {
            $constraints = [];

            foreach ($parameter as $name) {
                $constraints[(string) $name] = $pattern;
            }

            return $this->where($constraints);
        }

        return $this->where($parameter, $pattern);
    }
}
