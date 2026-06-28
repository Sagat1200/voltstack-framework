<?php

declare(strict_types=1);

namespace Quantum\HttpKernel;

use Closure;
use Quantum\Http\Request;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use RuntimeException;
use VoltStack\Framework\Application;

final class CompiledMiddlewarePipeline
{
    private Closure $executor;

    /**
     * @param array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface> $middlewares
     */
    private function __construct(
        private readonly array $middlewares,
        private readonly string $id,
    ) {
        $this->executor = $this->compileExecutor();
    }

    /**
     * @param array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface> $middlewares
     */
    public static function compile(array $middlewares): self
    {
        $normalized = array_values($middlewares);

        return new self($normalized, MiddlewareStack::signature($normalized));
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface>
     */
    public function middlewares(): array
    {
        return $this->middlewares;
    }

    public function handle(Application $app, Request $request, Closure $destination): mixed
    {
        $executor = $this->executor;

        return $executor($app, $request, $destination);
    }

    private function compileExecutor(): Closure
    {
        return array_reduce(
            array_reverse($this->middlewares),
            static fn(Closure $next, mixed $middleware): Closure => static function (
                Application $app,
                Request $request,
                Closure $destination,
            ) use ($middleware, $next): mixed {
                $nextClosure = static fn(Request $request): mixed => $next($app, $request, $destination);

                return self::handleMiddleware($app, $middleware, $request, $nextClosure);
            },
            static fn(Application $app, Request $request, Closure $destination): mixed => $destination($request),
        );
    }

    private static function handleMiddleware(Application $app, mixed $middleware, Request $request, Closure $next): mixed
    {
        if (is_string($middleware)) {
            $middleware = $app->make($middleware);
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