<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\HttpKernel\HttpKernel;
use Quantum\Middlewares\CsrfMiddleware;
use Quantum\Routing\Router;
use Quantum\Security\CsrfTokenManager;
use VoltStack\Framework\Application;

final class CsrfMiddlewareTest extends TestCase
{
    private string $basePath;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-csrf-' . uniqid('', true);

        if (! mkdir($concurrentDirectory = $this->basePath, 0777, true) && ! is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Unable to create test directory [%s].', $this->basePath));
        }

        $this->app = new Application($this->basePath);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_blocks_mutating_requests_without_a_valid_csrf_token(): void
    {
        $router = $this->app->make(Router::class);
        $router->post('/submit', fn() => 'ok');

        $kernel = $this->app->make(HttpKernel::class);
        $kernel->setMiddlewares([CsrfMiddleware::class]);

        $response = $kernel->handle(Request::create('/submit', 'POST'));

        self::assertSame(419, $response->statusCode());
        self::assertSame('text/html; charset=UTF-8', $response->headers()['Content-Type']);
        self::assertStringContainsString('Page Expired', $response->content());
        self::assertStringContainsString('CSRF token mismatch.', $response->content());
    }

    public function test_it_allows_mutating_requests_with_a_valid_csrf_token(): void
    {
        $router = $this->app->make(Router::class);
        $router->post('/submit', fn() => 'ok');

        $kernel = $this->app->make(HttpKernel::class);
        $kernel->setMiddlewares([CsrfMiddleware::class]);

        $response = $kernel->handle(Request::create(
            '/submit',
            'POST',
            [],
            ['_token' => $this->app->make(CsrfTokenManager::class)->token()],
        ));

        self::assertSame(200, $response->statusCode());
        self::assertSame('ok', $response->content());
    }

    public function test_it_skips_csrf_validation_for_safe_http_methods(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/preview', fn() => 'ok');

        $kernel = $this->app->make(HttpKernel::class);
        $kernel->setMiddlewares([CsrfMiddleware::class]);

        $response = $kernel->handle(Request::create('/preview', 'GET'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('ok', $response->content());
    }

    public function test_it_allows_route_level_opt_out_when_csrf_metadata_is_disabled(): void
    {
        $router = $this->app->make(Router::class);
        $router->post('/webhook', fn() => 'ok')
            ->middleware('csrf')
            ->csrf(false);

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/webhook', 'POST'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('ok', $response->content());
    }

    public function test_it_enforces_route_level_csrf_validation_for_mutating_http_requests(): void
    {
        $router = $this->app->make(Router::class);
        $router->post('/profile', fn() => 'ok')
            ->middleware('csrf');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/profile', 'POST'));

        self::assertSame(419, $response->statusCode());
        self::assertStringContainsString('CSRF token mismatch.', $response->content());
    }

    public function test_it_skips_the_global_csrf_middleware_for_internal_volt_action_requests(): void
    {
        $middleware = $this->app->make(CsrfMiddleware::class);
        $request = Request::create('/_volt/action', 'POST');
        $handled = false;

        $result = $middleware->handle($request, static function () use (&$handled): Response {
            $handled = true;

            return new Response('ok');
        });

        self::assertTrue($handled);
        self::assertInstanceOf(Response::class, $result);
        self::assertSame('ok', $result->content());
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
