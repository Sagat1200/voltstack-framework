<?php

declare(strict_types=1);

namespace Quantum\Middlewares;

use Closure;
use Quantum\Http\Request;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use Quantum\Security\CsrfTokenManager;
use Quantum\Security\Exceptions\CsrfTokenMismatchException;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly CsrfTokenManager $tokens)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->shouldVerifyToken($request)) {
            return $next($request);
        }

        $token = $request->header('X-CSRF-TOKEN') ?? $request->post('_token');

        if (! is_string($token) || ! $this->tokens->verify($token)) {
            throw new CsrfTokenMismatchException('CSRF token mismatch.');
        }

        return $next($request);
    }

    private function shouldVerifyToken(Request $request): bool
    {
        if (! $request->isConventionalHttpRequest()) {
            return false;
        }

        return match ($this->resolvePolicy($request)) {
            false => false,
            true => $request->isStateChangingMethod(),
            default => $request->isStateChangingMethod(),
        };
    }

    private function resolvePolicy(Request $request): ?bool
    {
        $policy = $request->routeMeta('csrf');

        if (is_bool($policy)) {
            return $policy;
        }

        if (! is_string($policy)) {
            return null;
        }

        return match (strtolower(trim($policy))) {
            'true', 'on', 'enable', 'enabled', 'require', 'required' => true,
            'false', 'off', 'disable', 'disabled', 'skip', 'ignore' => false,
            default => null,
        };
    }
}
