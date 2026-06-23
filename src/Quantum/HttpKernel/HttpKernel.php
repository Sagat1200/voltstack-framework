<?php

declare(strict_types=1);

namespace Quantum\HttpKernel;

use Quantum\Http\JsonResponse;
use Quantum\Http\HtmlDocumentBootstrapper;
use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use Quantum\Routing\Router;
use Quantum\View\View;
use Throwable;
use VoltStack\Framework\Application;
use VoltStack\Framework\Contracts\ExceptionHandler as ExceptionHandlerContract;
use VoltStack\Framework\Contracts\Kernel as KernelContract;
use VoltStack\Runtime\Component\Component;
use VoltStack\Runtime\Component\ComponentManager;
use VoltStack\Runtime\Context\ScopeManager;

class HttpKernel implements KernelContract
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
        $this->app->boot();
        $scope = $this->app->make(ScopeManager::class);
        $scope->begin($request);
        $response = null;

        try {
            $pipeline = new MiddlewarePipeline($this->app, $this->middlewares);

            $response = $pipeline->handle(
                $request,
                fn(Request $request): mixed => $this->router->dispatch($request),
            );

            $response = $this->toResponse($response);
        } catch (Throwable $exception) {
            $response = $this->app->make(ExceptionHandlerContract::class)->render($request, $exception);
        } finally {
            $scope->end();
        }

        return $this->bootstrapHtmlResponse($request, $response);
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
            return new Response($this->app->make(ComponentManager::class)->renderRoot($response));
        }

        if (is_string($response) || is_numeric($response)) {
            return new Response((string) $response);
        }

        if ($response === null) {
            return new Response('');
        }

        return new JsonResponse($response);
    }

    private function bootstrapHtmlResponse(Request $request, Response $response): Response
    {
        $bootstrapper = $this->app->make(HtmlDocumentBootstrapper::class);

        if (! $bootstrapper->shouldBootstrap($request, $response)) {
            return $response;
        }

        return $bootstrapper->bootstrap($response);
    }
}
