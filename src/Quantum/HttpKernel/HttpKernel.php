<?php

declare(strict_types=1);

namespace Quantum\HttpKernel;

use Quantum\Http\JsonResponse;
use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use Quantum\Routing\Exceptions\RouteNotFoundException;
use Quantum\Routing\Router;
use Quantum\View\View;
use Throwable;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\Component;
use VoltStack\Runtime\Component\ComponentManager;

class HttpKernel
{
    /**
     * @var array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface>
     */
    protected array $middlewares = [];

    public function __construct(
        protected Application $app,
        protected Router $router,
        ?array $middlewares = null,
    ) {
        if ($middlewares !== null) {
            $this->middlewares = $middlewares;
        }
    }

    /**
     * @param array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface> $middlewares
     */
    public function setMiddlewares(array $middlewares): void
    {
        $this->middlewares = $middlewares;
    }

    public function pushMiddleware(callable|string|MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function handle(Request $request): Response
    {
        try {
            $pipeline = new MiddlewarePipeline($this->app, $this->middlewares);

            $response = $pipeline->handle(
                $request,
                fn (Request $request): mixed => $this->router->dispatch($request),
            );

            return $this->toResponse($response);
        } catch (RouteNotFoundException) {
            return new Response('Not Found', 404);
        } catch (Throwable $exception) {
            throw $exception;
        }
    }

    protected function toResponse(mixed $response): Response
    {
        if ($response instanceof Response) {
            return $response;
        }

        if (is_array($response)) {
            return new JsonResponse($response);
        }

        if ($response instanceof View) {
            return new Response($response->render());
        }

        if ($response instanceof Component) {
            return new Response($this->app->make(ComponentManager::class)->render($response));
        }

        if (is_string($response) || is_numeric($response)) {
            return new Response((string) $response);
        }

        if ($response === null) {
            return new Response('');
        }

        return new JsonResponse($response);
    }
}
