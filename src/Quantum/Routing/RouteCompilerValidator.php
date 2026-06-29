<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Closure;
use Quantum\Routing\Exceptions\RouteCompilationException;

final class RouteCompilerValidator
{
    /**
     * @param iterable<int, CompiledRoute> $routes
     */
    public function validateRoutes(
        iterable $routes,
        bool $requireSerializableAction = false,
        bool $requireSerializableMiddlewares = false,
        bool $requireSerializableMetadata = false,
    ): void {
        foreach ($routes as $route) {
            $parameters = [];

            $this->validateTemplate($route, $route->uri(), 'uri', $parameters);

            if ($route->routeDomain() !== null) {
                $this->validateTemplate($route, $route->routeDomain(), 'domain', $parameters);
            }

            $this->validateConstraints($route, $parameters);

            if ($requireSerializableAction) {
                $this->validateSerializableAction($route);
            }

            if ($requireSerializableMiddlewares) {
                $this->validateSerializableMiddlewares($route);
            }

            if ($requireSerializableMetadata) {
                $this->validateSerializableMetadata($route->routeMetadata()->all(), $route->uri());
            }
        }
    }

    /**
     * @param array<string, true> $parameters
     */
    private function validateTemplate(CompiledRoute $route, string $template, string $kind, array &$parameters): void
    {
        if (substr_count($template, '{') !== substr_count($template, '}')) {
            throw new RouteCompilationException(sprintf(
                'Route [%s] contains malformed %s placeholders.',
                $route->uri(),
                $kind,
            ));
        }

        $strippedTemplate = preg_replace('/\{[^}]*\}/', '', $template) ?? $template;

        if (str_contains($strippedTemplate, '{') || str_contains($strippedTemplate, '}')) {
            throw new RouteCompilationException(sprintf(
                'Route [%s] contains malformed %s placeholders.',
                $route->uri(),
                $kind,
            ));
        }

        preg_match_all('/\{([^}]*)\}/', $template, $matches);

        foreach ($matches[1] ?? [] as $rawName) {
            $parameterName = trim((string) $rawName);

            if ($parameterName === '') {
                throw new RouteCompilationException(sprintf(
                    'Route [%s] contains malformed %s placeholders.',
                    $route->uri(),
                    $kind,
                ));
            }

            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $parameterName) !== 1) {
                throw new RouteCompilationException(sprintf(
                    'Route [%s] contains an invalid route parameter name [%s].',
                    $route->uri(),
                    $parameterName,
                ));
            }

            if (isset($parameters[$parameterName])) {
                throw new RouteCompilationException(sprintf(
                    'Route [%s] contains duplicate route parameter [%s].',
                    $route->uri(),
                    $parameterName,
                ));
            }

            $parameters[$parameterName] = true;
        }
    }

    /**
     * @param array<string, true> $parameters
     */
    private function validateConstraints(CompiledRoute $route, array $parameters): void
    {
        foreach ($route->definition()->constraints() as $parameter => $pattern) {
            if (! is_string($parameter) || trim($parameter) === '' || ! isset($parameters[$parameter])) {
                throw new RouteCompilationException(sprintf(
                    'Route [%s] contains a constraint for undefined parameter [%s].',
                    $route->uri(),
                    is_string($parameter) ? $parameter : '',
                ));
            }

            if (! is_string($pattern) || trim($pattern) === '') {
                throw new RouteCompilationException(sprintf(
                    'Route [%s] contains an invalid constraint definition for [%s].',
                    $route->uri(),
                    $parameter,
                ));
            }
        }
    }

    private function validateSerializableAction(CompiledRoute $route): void
    {
        $action = $route->action();

        if (is_string($action) && $action !== '') {
            return;
        }

        if (is_array($action) && count($action) === 2 && is_string($action[0] ?? null) && $action[0] !== '' && is_string($action[1] ?? null) && $action[1] !== '') {
            return;
        }

        if ($action instanceof Closure) {
            throw new RouteCompilationException(sprintf(
                'Route [%s] contains a closure action that cannot be serialized into the collection artifact.',
                $route->uri(),
            ));
        }

        throw new RouteCompilationException(sprintf(
            'Route [%s] contains a non-serializable action for the collection artifact.',
            $route->uri(),
        ));
    }

    private function validateSerializableMiddlewares(CompiledRoute $route): void
    {
        foreach ($route->routeMiddlewares() as $middleware) {
            if (! is_string($middleware) || $middleware === '') {
                throw new RouteCompilationException(sprintf(
                    'Route [%s] contains non-serializable middleware in its compiled pipeline.',
                    $route->uri(),
                ));
            }
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function validateSerializableMetadata(array $metadata, string $routeUri): void
    {
        foreach ($metadata as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                throw new RouteCompilationException(sprintf(
                    'Route [%s] contains a non-serializable metadata key.',
                    $routeUri,
                ));
            }

            $this->validateSerializableMetadataValue($value, $routeUri, $key);
        }
    }

    private function validateSerializableMetadataValue(mixed $value, string $routeUri, string $key): void
    {
        if (is_null($value) || is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $nestedKey => $nestedValue) {
                if (! is_int($nestedKey) && ! is_string($nestedKey)) {
                    throw new RouteCompilationException(sprintf(
                        'Route [%s] contains non-serializable metadata at [%s].',
                        $routeUri,
                        $key,
                    ));
                }

                $this->validateSerializableMetadataValue($nestedValue, $routeUri, $key);
            }

            return;
        }

        throw new RouteCompilationException(sprintf(
            'Route [%s] contains non-serializable metadata at [%s].',
            $routeUri,
            $key,
        ));
    }
}
