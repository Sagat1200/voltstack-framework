<?php

declare(strict_types=1);

namespace VoltStack\Framework\Contracts;

use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;

interface Kernel
{
    public function handle(Request $request): Response;

    /**
     * @param array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface> $middlewares
     */
    public function setMiddlewares(array $middlewares): void;

    public function pushMiddleware(callable|string|MiddlewareInterface $middleware): void;
}