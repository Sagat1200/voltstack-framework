<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Quantum\Routing\Exceptions\RouteCompilationException;

final class ConstraintCompiler
{
    /**
     * @param array<string, string> $constraints
     * @return array<string, string>
     */
    public function compile(array $constraints, string $routeUri): array
    {
        $compiled = [];

        foreach ($constraints as $parameter => $pattern) {
            if (! is_string($parameter) || trim($parameter) === '' || ! is_string($pattern) || trim($pattern) === '') {
                throw new RouteCompilationException(sprintf(
                    'Route [%s] contains an invalid constraint definition for [%s].',
                    $routeUri,
                    is_string($parameter) ? $parameter : '',
                ));
            }

            $compiled[$parameter] = $this->compilePattern($pattern, $routeUri, $parameter);
        }

        return $compiled;
    }

    private function compilePattern(string $pattern, string $routeUri, string $parameter): string
    {
        $normalized = $this->normalizeCapturingGroups(trim($pattern));
        $regex = '/^(?:' . $normalized . ')$/';

        if (@preg_match($regex, '') === false) {
            throw new RouteCompilationException(sprintf(
                'Route [%s] contains an invalid constraint pattern for [%s].',
                $routeUri,
                $parameter,
            ));
        }

        return $normalized;
    }

    private function normalizeCapturingGroups(string $pattern): string
    {
        $normalized = '';
        $length = strlen($pattern);
        $escaped = false;

        for ($index = 0; $index < $length; $index++) {
            $character = $pattern[$index];

            if ($escaped) {
                $normalized .= $character;
                $escaped = false;

                continue;
            }

            if ($character === '\\') {
                $normalized .= $character;
                $escaped = true;

                continue;
            }

            if ($character === '(') {
                $next = $pattern[$index + 1] ?? '';

                if ($next === '?') {
                    $normalized .= '(';

                    continue;
                }

                $normalized .= '(?:';

                continue;
            }

            $normalized .= $character;
        }

        return $normalized;
    }
}
