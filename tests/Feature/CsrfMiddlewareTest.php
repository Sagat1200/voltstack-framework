<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use Quantum\HttpKernel\HttpKernel;
use Quantum\Middlewares\CsrfMiddleware;
use Quantum\Routing\Router;
use Quantum\Security\CsrfTokenManager;
use VoltStack\Framework\Application;

final class CsrfMiddlewareTest extends TestCase
{
    public function test_it_blocks_mutating_requests_without_a_valid_csrf_token(): void
    {
        $app = new Application(sys_get_temp_dir());
        $router = $app->make(Router::class);
        $router->post('/submit', fn () => 'ok');

        $kernel = $app->make(HttpKernel::class);
        $kernel->setMiddlewares([CsrfMiddleware::class]);

        $response = $kernel->handle(Request::create('/submit', 'POST'));

        self::assertSame(419, $response->statusCode());
        self::assertSame('text/html; charset=UTF-8', $response->headers()['Content-Type']);
        self::assertStringContainsString('Page Expired', $response->content());
        self::assertStringContainsString('CSRF token mismatch.', $response->content());
    }

    public function test_it_allows_mutating_requests_with_a_valid_csrf_token(): void
    {
        $app = new Application(sys_get_temp_dir());
        $router = $app->make(Router::class);
        $router->post('/submit', fn () => 'ok');

        $kernel = $app->make(HttpKernel::class);
        $kernel->setMiddlewares([CsrfMiddleware::class]);

        $response = $kernel->handle(Request::create(
            '/submit',
            'POST',
            [],
            ['_token' => $app->make(CsrfTokenManager::class)->token()],
        ));

        self::assertSame(200, $response->statusCode());
        self::assertSame('ok', $response->content());
    }
}
