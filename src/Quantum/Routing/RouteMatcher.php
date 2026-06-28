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
        $path = $request->path();
        $method = $request->method();
        $allowedMethods = [];

        foreach ($routes as $route) {
            $parameters = $route->matches($request);

            if ($parameters === null) {
                $pathParameters = $route->matchPath($path);

                if ($pathParameters === null) {
                    continue;
                }

                $allowedMethods = [...$allowedMethods, ...$route->methods()];
                continue;
            }

            return new RouteMatch($route, $parameters, $method);
        }

        if ($method === 'HEAD') {
            foreach ($routes as $route) {
                if (! $route->allowsMethod('GET')) {
                    continue;
                }

                $parameters = $route->matchPath($path);

                if ($parameters === null) {
                    continue;
                }

                return new RouteMatch($route, $parameters, 'GET', true);
            }
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
