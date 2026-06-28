<?php

declare(strict_types=1);

namespace Quantum\HttpKernel;

use Quantum\Http\JsonResponse;
use Quantum\Http\HtmlDocumentBootstrapper;
use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\HttpKernel\MiddlewareAliasRegistry;
use Quantum\HttpKernel\MiddlewareStack;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use Quantum\Routing\Router;
use Quantum\Routing\Dispatching\ResponseNormalizer;
use Throwable;
use VoltStack\Framework\Application;
use VoltStack\Framework\Contracts\ExceptionHandler as ExceptionHandlerContract;
use VoltStack\Framework\Contracts\Kernel as KernelContract;
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
        protected ResponseNormalizer $normalizer,
        ?array $middlewares = null,
    ) {
        if ($middlewares !== null) {
            $this->setMiddlewares($middlewares);
        }
    }

    /**
     * @param array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface> $middlewares
     */
    public function setMiddlewares(array $middlewares): void
    {
        $this->middlewares = MiddlewareStack::deduplicate($this->middlewareAliases()->resolveMany($middlewares));
    }

    public function pushMiddleware(callable|string|MiddlewareInterface $middleware): void
    {
        $this->middlewares = MiddlewareStack::deduplicate([
            ...$this->middlewares,
            $this->middlewareAliases()->resolve($middleware),
        ]);
    }

    public function aliasMiddleware(string $alias, mixed $middleware): void
    {
        $this->middlewareAliases()->alias($alias, $middleware);
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

            $response = $this->normalizer->normalize($response);
        } catch (Throwable $exception) {
            $response = $this->app->make(ExceptionHandlerContract::class)->render($request, $exception);
        } finally {
            $scope->end();
        }

        $response = $this->bootstrapHtmlResponse($request, $response);

        if ($request->method() === 'HEAD') {
            $response->setContent('');
        }

        return $response;
    }

    private function bootstrapHtmlResponse(Request $request, Response $response): Response
    {
        $bootstrapper = $this->app->make(HtmlDocumentBootstrapper::class);

        if (! $bootstrapper->shouldBootstrap($request, $response)) {
            return $response;
        }

        return $bootstrapper->bootstrap($response);
    }

    private function middlewareAliases(): MiddlewareAliasRegistry
    {
        return $this->app->make(MiddlewareAliasRegistry::class);
    }
}
