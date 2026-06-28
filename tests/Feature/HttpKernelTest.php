<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use Quantum\HttpKernel\HttpKernel;
use Quantum\Routing\Exceptions\DuplicateRouteException;
use Quantum\Routing\Exceptions\DuplicateRouteNameException;
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
        $router->get('/', fn() => 'VoltStack Home');

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
        $router->get('/middleware', fn() => new Response('ok'));

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
        self::assertStringContainsString('Page Not Found', $response->content());
        self::assertSame('text/html; charset=UTF-8', $response->headers()['Content-Type']);
        self::assertStringContainsString('<meta name="volt-document" content="reload"', $response->content());
        self::assertStringContainsString('<meta name="volt-navigation-mode" content="reload"', $response->content());
        self::assertStringContainsString('<body data-volt-document="reload">', $response->content());
        self::assertStringNotContainsString('data-volt-navigation-mode="auto"', $response->content());
    }

    public function test_it_returns_method_not_allowed_with_allow_header_when_path_exists_for_other_methods(): void
    {
        $router = $this->app->make(Router::class);
        $router->post('/submit', fn() => 'stored');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/submit', 'GET'));

        self::assertSame(405, $response->statusCode());
        self::assertSame('POST, OPTIONS', $response->headers()['Allow']);
        self::assertStringContainsString('Method Not Allowed', $response->content());
    }

    public function test_it_falls_back_to_get_routes_for_head_requests(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/headable', fn() => new Response('body', 200, [
            'X-Route' => 'get',
        ]));

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/headable', 'HEAD'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('', $response->content());
        self::assertSame('get', $response->headers()['X-Route']);
    }

    public function test_it_prefers_explicit_head_routes_over_get_fallback(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/head-explicit', fn() => new Response('get-body', 200, [
            'X-Route' => 'get',
        ]));
        $router->head('/head-explicit', fn() => new Response('head-body', 200, [
            'X-Route' => 'head',
        ]));

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/head-explicit', 'HEAD'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('', $response->content());
        self::assertSame('head', $response->headers()['X-Route']);
    }

    public function test_it_returns_automatic_options_responses_with_allow_header(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/options-target', fn() => 'ok');
        $router->post('/options-target', fn() => 'stored');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/options-target', 'OPTIONS'));

        self::assertSame(204, $response->statusCode());
        self::assertSame('', $response->content());
        self::assertSame('GET, HEAD, POST, OPTIONS', $response->headers()['Allow']);
    }

    public function test_it_rejects_duplicate_routes_during_registration(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/users', fn() => 'first');

        $this->expectException(DuplicateRouteException::class);
        $this->expectExceptionMessage('A route is already registered for [GET] /users.');

        $router->get('/users', fn() => 'second');
    }

    public function test_it_rejects_duplicate_route_names_during_registration_flow(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/users', fn() => 'first')->name('users.index');

        $this->expectException(DuplicateRouteNameException::class);
        $this->expectExceptionMessage('A route is already registered with the name [users.index].');

        $router->post('/users', fn() => 'second')->name('users.index');
    }

    public function test_it_returns_not_found_when_a_route_constraint_does_not_match(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/users/{id}', fn(string $id) => $id)->whereNumber('id');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/users/abc'));

        self::assertSame(404, $response->statusCode());
        self::assertStringContainsString('Page Not Found', $response->content());
    }

    public function test_it_renders_server_errors_as_reload_only_documents(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/boom', static function (): never {
            throw new \RuntimeException('Boom');
        });

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/boom'));

        self::assertSame(500, $response->statusCode());
        self::assertStringContainsString('Server Error', $response->content());
        self::assertStringContainsString('<meta name="volt-document" content="reload"', $response->content());
        self::assertStringContainsString('<meta name="volt-navigation-mode" content="reload"', $response->content());
        self::assertStringContainsString('<body data-volt-document="reload">', $response->content());
        self::assertStringNotContainsString('data-volt-navigation-mode="auto"', $response->content());
    }

    public function test_it_bootstraps_document_contract_markers_for_full_html_documents(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/document', fn() => '<!DOCTYPE html><html><body><main>Document</main></body></html>');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/document'));

        self::assertStringContainsString('<body data-volt-document="spa" data-volt-navigation-mode="auto">', $response->content());
        self::assertStringContainsString('data-volt-runtime="true"', $response->content());
        self::assertMatchesRegularExpression('/<script data-volt-runtime="true" src="\/_volt\/runtime\.js\?v=\d+" defer><\/script>/', $response->content());
    }

    public function test_it_serves_the_runtime_as_a_cacheable_javascript_asset(): void
    {
        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/_volt/runtime.js'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('application/javascript; charset=UTF-8', $response->headers()['Content-Type']);
        self::assertSame('public, max-age=31536000, immutable', $response->headers()['Cache-Control']);
        self::assertSame('nosniff', $response->headers()['X-Content-Type-Options']);
        self::assertArrayHasKey('ETag', $response->headers());
        self::assertArrayHasKey('Last-Modified', $response->headers());
        self::assertStringContainsString('window.Volt.components = createPublicComponentsApi();', $response->content());
        self::assertStringContainsString('volt:component-destroyed', $response->content());
        self::assertStringContainsString('function cleanupRuntimeOrphans()', $response->content());
        self::assertStringContainsString('window.Volt.telemetry = createPublicTelemetryApi();', $response->content());
    }

    public function test_it_preserves_declared_document_navigation_mode_when_bootstrapping_html(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/document-reload', fn() => '<!DOCTYPE html><html><head><meta name="volt-navigation-mode" content="reload"></head><body><main>Reload</main></body></html>');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/document-reload'));

        self::assertStringContainsString('<meta name="volt-navigation-mode" content="reload">', $response->content());
        self::assertStringContainsString('<body data-volt-document="spa">', $response->content());
        self::assertStringNotContainsString('data-volt-navigation-mode="auto"', $response->content());
    }

    public function test_it_marks_reload_only_documents_without_forcing_auto_navigation_mode(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/document-reload-only', fn() => '<!DOCTYPE html><html><head><meta name="volt-document" content="reload-only"></head><body><main>Reload only</main></body></html>');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/document-reload-only'));

        self::assertStringContainsString('<meta name="volt-document" content="reload-only">', $response->content());
        self::assertStringContainsString('<body data-volt-document="reload">', $response->content());
        self::assertStringNotContainsString('data-volt-navigation-mode="auto"', $response->content());
    }

    public function test_it_preserves_explicit_body_level_reload_only_document_marker(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/document-body-reload-only', fn() => '<!DOCTYPE html><html><body data-volt-document="reload"><main>Reload body</main></body></html>');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/document-body-reload-only'));

        self::assertStringContainsString('<body data-volt-document="reload">', $response->content());
        self::assertStringNotContainsString('data-volt-navigation-mode="auto"', $response->content());
    }

    public function test_it_does_not_bootstrap_attachment_responses_as_spa_documents(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/export', fn() => new Response(
            '<!DOCTYPE html><html><body><main>Export</main></body></html>',
            200,
            [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="report.html"',
            ],
        ));

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/export'));

        self::assertStringNotContainsString('data-volt-runtime="true"', $response->content());
        self::assertStringNotContainsString('data-volt-document="spa"', $response->content());
        self::assertSame('attachment; filename="report.html"', $response->headers()['Content-Disposition']);
    }

    public function test_it_does_not_bootstrap_non_html_download_like_responses(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/report-json', fn() => new Response(
            '{"ok":true}',
            200,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        ));

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/report-json'));

        self::assertSame('{"ok":true}', $response->content());
        self::assertStringNotContainsString('data-volt-runtime="true"', $response->content());
        self::assertSame('application/json; charset=UTF-8', $response->headers()['Content-Type']);
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
