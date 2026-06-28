<?php

declare(strict_types=1);

namespace Quantum\HttpKernel;

final class MiddlewareStack
{
    /**
     * @param array<int, mixed> $middlewares
     * @return array<int, mixed>
     */
    public static function deduplicate(array $middlewares): array
    {
        $resolved = [];
        $seen = [];

        foreach ($middlewares as $middleware) {
            $fingerprint = self::fingerprint($middleware);

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $resolved[] = $middleware;
        }

        return $resolved;
    }

    /**
     * @param array<int, mixed> $middlewares
     */
    public static function signature(array $middlewares): string
    {
        return sha1(implode('|', array_map(
            static fn(mixed $middleware): string => self::fingerprint($middleware),
            array_values($middlewares),
        )));
    }

    private static function fingerprint(mixed $middleware): string
    {
        if (is_string($middleware)) {
            return 'string:' . $middleware;
        }

        if ($middleware instanceof \Closure) {
            return 'closure:' . spl_object_id($middleware);
        }

        if (is_object($middleware)) {
            return 'object:' . $middleware::class . ':' . spl_object_id($middleware);
        }

        if (is_array($middleware)) {
            return 'array:' . implode('|', array_map(
                static fn(mixed $item): string => self::fingerprint($item),
                array_values($middleware),
            ));
        }

        if (is_resource($middleware)) {
            return 'resource:' . get_resource_type($middleware) . ':' . get_resource_id($middleware);
        }

        return 'scalar:' . get_debug_type($middleware) . ':' . var_export($middleware, true);
    }
}
