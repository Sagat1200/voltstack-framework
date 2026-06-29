<?php

declare(strict_types=1);

namespace Quantum\Routing\Exceptions;

use RuntimeException;

final class RouteUrlGenerationException extends RuntimeException
{
    public static function forUnknownRoute(string $name): self
    {
        return new self(sprintf(
            'Route [%s] is not registered and cannot be generated.',
            trim($name),
        ));
    }

    public static function forMissingParameter(string $routeName, string $parameter): self
    {
        return new self(sprintf(
            'Missing required route parameter [%s] for route [%s].',
            $parameter,
            $routeName,
        ));
    }

    public static function forInvalidParameter(string $routeName, string $parameter, mixed $value): self
    {
        return new self(sprintf(
            'Route parameter [%s] for route [%s] must be scalar, stringable or enum, %s given.',
            $parameter,
            $routeName,
            get_debug_type($value),
        ));
    }

    public static function forInvalidQuery(string $routeName): self
    {
        return new self(sprintf(
            'Route query payload for route [%s] must be an array.',
            $routeName,
        ));
    }

    public static function forInvalidFragment(string $routeName, mixed $value): self
    {
        return new self(sprintf(
            'Route fragment for route [%s] must be scalar, stringable or enum, %s given.',
            $routeName,
            get_debug_type($value),
        ));
    }

    public static function forMissingBaseUrl(): self
    {
        return new self('Absolute route generation requires `app.url` or an active request host.');
    }

    public static function forInvalidBaseUrl(string $baseUrl): self
    {
        return new self(sprintf(
            'Configured app.url [%s] is not a valid absolute URL.',
            $baseUrl,
        ));
    }
}
