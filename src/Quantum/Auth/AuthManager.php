<?php

declare(strict_types=1);

namespace Quantum\Auth;

use VoltStack\Runtime\Context\RuntimeContext;

final class AuthManager
{
    public function user(): mixed
    {
        return $this->context()->get('auth.user');
    }

    public function setUser(mixed $user): void
    {
        $this->context()->set('auth.user', $user);
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function id(): mixed
    {
        $user = $this->user();

        if (is_object($user) && isset($user->id)) {
            return $user->id;
        }

        if (is_array($user) && array_key_exists('id', $user)) {
            return $user['id'];
        }

        return null;
    }

    public function logout(): void
    {
        $this->context()->set('auth.user', null);
    }

    private function context(): RuntimeContext
    {
        $context = RuntimeContext::current();

        if ($context === null) {
            throw new \RuntimeException('No active runtime context is available for auth access.');
        }

        return $context;
    }
}
