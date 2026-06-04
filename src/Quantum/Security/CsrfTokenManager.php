<?php

declare(strict_types=1);

namespace Quantum\Security;

use VoltStack\Framework\Application;

final class CsrfTokenManager
{
    public function __construct(private readonly Application $app)
    {
    }

    public function token(): string
    {
        return hash_hmac('sha256', 'voltstack-csrf-token', $this->secret());
    }

    public function verify(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        return hash_equals($this->token(), $token);
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
