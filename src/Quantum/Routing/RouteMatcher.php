<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Quantum\Http\Request;
use Quantum\Routing\Exceptions\MethodNotAllowedException;
use Quantum\Routing\Exceptions\RouteNotFoundException;

final class RouteMatcher
{
    public function match(Request $request, RouteCollection $routes): RouteMatch
    {
        $host = $request->host();
        $path = $request->path();
        $method = $request->method();
        $allowedMethods = [];
        $preferredMatch = null;
        $fallbackMatch = null;
        $preferredHeadFallback = null;
        $fallbackHeadFallback = null;

        foreach ($routes as $route) {
            $parameters = $route->matchTarget($host, $path);

            if ($parameters === null) {
                continue;
            }

            if ($route->allowsMethod($method)) {
                $match = new RouteMatch($route, $parameters, $method);

                if ($route->routeDomain() !== null) {
                    $preferredMatch ??= $match;
                } else {
                    $fallbackMatch ??= $match;
                }

                continue;
            }

            if ($method === 'HEAD' && $route->allowsMethod('GET')) {
                $match = new RouteMatch($route, $parameters, 'GET', true);

                if ($route->routeDomain() !== null) {
                    $preferredHeadFallback ??= $match;
                } else {
                    $fallbackHeadFallback ??= $match;
                }
                continue;
            }

            $allowedMethods = [...$allowedMethods, ...$route->methods()];
        }

        if ($preferredMatch !== null) {
            return $preferredMatch;
        }

        if ($fallbackMatch !== null) {
            return $fallbackMatch;
        }

        if ($preferredHeadFallback !== null) {
            return $preferredHeadFallback;
        }

        if ($fallbackHeadFallback !== null) {
            return $fallbackHeadFallback;
        }

        if ($allowedMethods !== []) {
            throw new MethodNotAllowedException($method, $this->normalizeAllowedMethods($allowedMethods), $path);
        }

        throw new RouteNotFoundException(sprintf(
            'No route matched [%s] %s.',
            $method,
            $path,
        ));
    }

    /**
     * @param array<int, string> $methods
     * @return array<int, string>
     */
    private function normalizeAllowedMethods(array $methods): array
    {
        $methods = array_map('strtoupper', $methods);

        if (in_array('GET', $methods, true)) {
            $methods[] = 'HEAD';
        }

        $methods[] = 'OPTIONS';
        $methods = array_values(array_unique($methods));
        $priority = array_flip(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']);

        usort($methods, static function (string $left, string $right) use ($priority): int {
            return ($priority[$left] ?? PHP_INT_MAX) <=> ($priority[$right] ?? PHP_INT_MAX);
        });

        return $methods;
    }
}
