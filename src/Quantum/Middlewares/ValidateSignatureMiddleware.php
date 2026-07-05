<?php

declare(strict_types=1);

namespace Quantum\Middlewares;

use Closure;
use Quantum\Http\Request;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use Quantum\Routing\Router;
use Quantum\Security\Exceptions\InvalidSignatureException;

final class ValidateSignatureMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Router $router,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->router->hasValidSignature($request)) {
            throw new InvalidSignatureException('Invalid signature.');
        }

        return $next($request);
    }
}
