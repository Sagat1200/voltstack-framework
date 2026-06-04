<?php

declare(strict_types=1);

namespace Quantum\Middlewares;

use Closure;
use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use Quantum\Security\CsrfTokenManager;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly CsrfTokenManager $tokens)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $token = $request->header('X-CSRF-TOKEN') ?? $request->post('_token');

        if (! is_string($token) || ! $this->tokens->verify($token)) {
            return new Response('CSRF token mismatch.', 419);
        }

        return $next($request);
    }
}
