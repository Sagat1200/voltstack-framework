<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use Quantum\HttpKernel\HttpKernel;
use Quantum\Routing\Router;
use VoltStack\Framework\Application;

final class HttpKernelTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(sys_get_temp_dir());
    }

    public function test_it_dispatches_a_closure_route_and_returns_a_response(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/', fn () => 'VoltStack Home');

        $kernel = $this->app->make(HttpKernel::class);
        $response = $kernel->handle(Request::create('/'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('VoltStack Home', $response->content());
    }

    public function test_it_resolves_invokable_route_actions_and_route_parameters(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/users/{id}', TestInvokableController::class);

        $kernel = $this->app->make(HttpKernel::class);
        $response = $kernel->handle(Request::create('/users/42'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('application/json; charset=UTF-8', $response->headers()['Content-Type']);
        self::assertSame('{"id":"42","message":"resolved","path":"\\/users\\/42"}', $response->content());
    }

    public function test_it_runs_middlewares_around_the_route_dispatcher(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/middleware', fn () => new Response('ok'));

        $kernel = $this->app->make(HttpKernel::class);
        $kernel->setMiddlewares([TestHeaderMiddleware::class]);

        $response = $kernel->handle(Request::create('/middleware'));

        self::assertSame('passed', $response->headers()['X-Middleware']);
    }

    public function test_it_returns_a_not_found_response_when_no_route_matches(): void
    {
        $kernel = $this->app->make(HttpKernel::class);
        $response = $kernel->handle(Request::create('/missing'));

        self::assertSame(404, $response->statusCode());
        self::assertSame('Not Found', $response->content());
    }
}

final class TestInvokableController
{
    public function __invoke(Request $request, string $id, TestResolvedDependency $dependency): array
    {
        return [
            'id' => $id,
            'message' => $dependency->message,
            'path' => $request->path(),
        ];
    }
}

final class TestResolvedDependency
{
    public string $message = 'resolved';
}

final class TestHeaderMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): mixed
    {
        $response = $next($request);

        if ($response instanceof Response) {
            $response->header('X-Middleware', 'passed');
        }

        return $response;
    }
}
