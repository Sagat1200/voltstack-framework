<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

use VoltStack\Framework\Application;

final class Checksum
{
    public function __construct(private readonly Application $app) {}

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $meta
     */
    public function sign(string $component, array $state, array $meta = []): string
    {
        $payload = json_encode([
            'component' => $component,
            'state' => $state,
            'meta' => $meta,
        ], JSON_THROW_ON_ERROR);

        return hash_hmac('sha256', $payload, $this->secret());
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $meta
     */
    public function verify(string $component, array $state, string $signature, array $meta = []): bool
    {
        return hash_equals($this->sign($component, $state, $meta), $signature);
    }

    private function secret(): string
    {
        $secret = (string) $this->app->config('app.key', '');

        if ($secret !== '') {
            return $secret;
        }

        return 'voltstack|' . $this->app->basePath();
    }
}