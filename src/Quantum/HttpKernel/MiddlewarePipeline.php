<?php

declare(strict_types=1);

namespace Quantum\HttpKernel;

use Closure;
use Quantum\Http\Request;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use RuntimeException;
use VoltStack\Framework\Application;

final class MiddlewarePipeline
{
    /**
     * @param array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly Application $app,
        private array $middlewares = [],
    ) {
    }

    /**
     * @param array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface> $middlewares
     */
    public function through(array $middlewares): self
    {
        $this->middlewares = $middlewares;

        return $this;
    }

    public function handle(Request $request, Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            fn (Closure $next, mixed $middleware): Closure => fn (Request $request): mixed => $this->handleMiddleware($middleware, $request, $next),
            $destination,
        );

        return $pipeline($request);
    }

    private function handleMiddleware(mixed $middleware, Request $request, Closure $next): mixed
    {
        if (is_string($middleware)) {
            $middleware = $this->app->make($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->handle($request, $next);
        }

        if (is_callable($middleware)) {
            return $middleware($request, $next);
        }

        throw new RuntimeException('Invalid middleware provided to the HTTP kernel.');
    }
}
