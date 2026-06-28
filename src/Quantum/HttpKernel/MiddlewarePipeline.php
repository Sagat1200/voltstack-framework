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
    private CompiledMiddlewarePipeline $compiled;

    /**
     * @param array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly Application $app,
        private array $middlewares = [],
    ) {
        $this->compiled = CompiledMiddlewarePipeline::compile($middlewares);
    }

    /**
     * @param array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface> $middlewares
     */
    public function through(array $middlewares): self
    {
        $this->middlewares = $middlewares;
        $this->compiled = CompiledMiddlewarePipeline::compile($middlewares);

        return $this;
    }

    public function handle(Request $request, Closure $destination): mixed
    {
        return $this->compiled->handle($this->app, $request, $destination);
    }

    public function compiled(): CompiledMiddlewarePipeline
    {
        return $this->compiled;
    }
}
