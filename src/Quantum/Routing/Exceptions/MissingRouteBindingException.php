<?php

declare(strict_types=1);

namespace Quantum\Routing\Exceptions;

final class MissingRouteBindingException extends \RuntimeException
{
    public function __construct(
        private readonly string $argument,
        private readonly string $routeUri,
        private readonly string $parameter,
        private readonly string $value,
        private readonly string $bindingClass,
    ) {
        parent::__construct(sprintf(
            'Unable to resolve route binding [%s] for route [%s].',
            $argument,
            $routeUri,
        ));
    }

    public function argument(): string
    {
        return $this->argument;
    }

    public function routeUri(): string
    {
        return $this->routeUri;
    }

    public function parameter(): string
    {
        return $this->parameter;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function bindingClass(): string
    {
        return $this->bindingClass;
    }
}
