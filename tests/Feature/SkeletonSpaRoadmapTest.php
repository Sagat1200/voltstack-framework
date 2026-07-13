<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Quantum\Bootstrap\Bootstrapper;
use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\HttpKernel\HttpKernel;
use Quantum\Routing\Router;
use VoltStack\Framework\Application;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class SkeletonSpaRoadmapTest extends TestCase
{
    private static string $skeletonBasePath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$skeletonBasePath = self::locateSkeletonBasePath();

        require_once self::$skeletonBasePath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    }

    public function test_routing_lab_index_exposes_public_navigation_targets(): void
    {
        $response = $this->handleSkeletonRequest('/routing-lab');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Routing Lab', $response->content());
        self::assertStringContainsString('href="/routing-lab/users/15"', $response->content());
        self::assertStringContainsString('href="/routing-lab/reports/export"', $response->content());
        self::assertStringContainsString('href="/routing-lab/private"', $response->content());
        self::assertStringContainsString('/_volt/routes-manifest.json', $response->content());
        self::assertStringContainsString('volt:navigate', $response->content());
    }

    public function test_home_screen_is_spa_capable_from_first_render(): void
    {
        $response = $this->handleSkeletonRequest('/');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('VoltStack Framework', $response->content());
        self::assertStringContainsString('href="/spaReactive"', $response->content());
        self::assertStringContainsString('volt:navigate', $response->content());
        self::assertStringContainsString('data-volt-document="spa"', $response->content());
        self::assertStringContainsString('data-volt-navigation-mode="auto"', $response->content());
        self::assertStringContainsString('data-volt-layout="app"', $response->content());
        self::assertSame(1, substr_count($response->content(), 'data-volt-runtime="true"'));
        self::assertMatchesRegularExpression('/<script data-volt-runtime="true" src="\/_volt\/runtime\.js\?v=\d+" defer><\/script>/', $response->content());
    }

    public function test_home_first_click_target_emits_spa_navigation_payload(): void
    {
        $home = $this->handleSkeletonRequest('/');
        $navigation = $this->handleSkeletonNavigationRequest('/spaReactive');
        $payload = $this->decodeNavigationPayload($navigation);

        self::assertSame(200, $home->statusCode(), $home->content());
        self::assertStringContainsString('href="/spaReactive"', $home->content());
        self::assertStringContainsString('volt:navigate', $home->content());

        self::assertSame(200, $navigation->statusCode(), $navigation->content());
        self::assertSame('/spaReactive', $payload['navigation']['target'] ?? null);
        self::assertSame('spaReactive', $payload['screen']['route'] ?? null);
        self::assertArrayHasKey('policy', $payload);
        self::assertNull($payload['redirect'] ?? null);
        self::assertNull($payload['error'] ?? null);
    }

    public function test_traditional_controller_view_can_embed_an_interactive_island(): void
    {
        $response = $this->handleSkeletonRequest('/islandExample');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Controller + View + Isla Interactiva', $response->content());
        self::assertStringContainsString('data-volt-root="true"', $response->content());
        self::assertStringContainsString('data-volt-component="App\\View\\Components\\IslandCounter"', $response->content());
        self::assertStringContainsString('volt:click="increment"', $response->content());
        self::assertStringContainsString('data-volt-layout="app"', $response->content());
        self::assertSame(1, substr_count($response->content(), 'data-volt-runtime="true"'));
        self::assertStringContainsString(
            '&quot;meta&quot;:{&quot;route&quot;:{&quot;name&quot;:&quot;islandExample&quot;',
            $response->content(),
        );
    }

    public function test_island_example_emits_spa_navigation_payload(): void
    {
        $navigation = $this->handleSkeletonNavigationRequest('/islandExample');
        $payload = $this->decodeNavigationPayload($navigation);

        self::assertSame(200, $navigation->statusCode(), $navigation->content());
        self::assertSame('/islandExample', $payload['navigation']['target'] ?? null);
        self::assertSame('islandExample', $payload['screen']['route'] ?? null);
    }

    public function test_traditional_controller_view_without_layout_is_still_spa_capable(): void
    {
        $response = $this->handleSkeletonRequest('/noLayoutExample');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Vista Tradicional Sin Layout', $response->content());
        self::assertStringContainsString('href="/"', $response->content());
        self::assertStringContainsString('href="/islandExample"', $response->content());
        self::assertStringContainsString('volt:navigate', $response->content());
        self::assertStringContainsString('data-volt-document="spa"', $response->content());
        self::assertStringContainsString('data-volt-navigation-mode="auto"', $response->content());
        self::assertStringNotContainsString('data-volt-layout=', $response->content());
        self::assertSame(1, substr_count($response->content(), 'data-volt-runtime="true"'));
    }

    public function test_no_layout_example_emits_spa_navigation_payload_without_runtime_layout_hint(): void
    {
        $navigation = $this->handleSkeletonNavigationRequest('/noLayoutExample');
        $payload = $this->decodeNavigationPayload($navigation);

        self::assertSame(200, $navigation->statusCode(), $navigation->content());
        self::assertSame('/noLayoutExample', $payload['navigation']['target'] ?? null);
        self::assertSame('noLayoutExample', $payload['screen']['route'] ?? null);
        self::assertNull($payload['policy']['document'] ?? null);
        self::assertNull($payload['policy']['navigation'] ?? null);
        self::assertNull($payload['runtime']['layout'] ?? null);
        self::assertStringContainsString('data-volt-document="spa"', $navigation->content());
        self::assertStringContainsString('data-volt-navigation-mode="auto"', $navigation->content());
    }

    public function test_routing_lab_navigation_payload_exposes_reload_and_redirect_contracts(): void
    {
        $reload = $this->handleSkeletonNavigationRequest('/routing-lab/reports/export');
        $reloadPayload = $this->decodeNavigationPayload($reload);
        $redirect = $this->handleSkeletonNavigationRequest('/routing-lab/private');
        $redirectPayload = $this->decodeNavigationPayload($redirect);

        self::assertSame(200, $reload->statusCode(), $reload->content());
        self::assertSame('/routing-lab/reports/export', $reloadPayload['navigation']['target'] ?? null);
        self::assertSame('routing.lab.reports.export', $reloadPayload['screen']['route'] ?? null);
        self::assertSame('reload', $reloadPayload['policy']['document'] ?? null);
        self::assertSame('reload', $reloadPayload['policy']['navigation'] ?? null);
        self::assertSame('routing-lab', $reloadPayload['runtime']['layout'] ?? null);
        self::assertSame('soft-edge', $reloadPayload['runtime']['transition'] ?? null);
        self::assertFalse($reloadPayload['runtime']['hydrate'] ?? true);

        self::assertSame(302, $redirect->statusCode(), $redirect->content());
        self::assertSame('/login', $redirectPayload['navigation']['target'] ?? null);
        self::assertSame('routing.lab.private', $redirectPayload['screen']['route'] ?? null);
        self::assertSame([
            'location' => '/login',
            'status' => 302,
        ], $redirectPayload['redirect'] ?? null);
        self::assertSame('routing-lab', $redirectPayload['runtime']['layout'] ?? null);
    }

    public function test_skeleton_layout_emits_stable_head_and_layout_markers_for_routing_lab(): void
    {
        $response = $this->handleSkeletonRequest('/routing-lab');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('<meta charset="UTF-8" data-volt-head-key="document-charset">', $response->content());
        self::assertStringContainsString(
            '<meta name="viewport" content="width=device-width, initial-scale=1.0" data-volt-head-key="document-viewport">',
            $response->content(),
        );
        self::assertStringContainsString('<body class="bg-slate-950 text-slate-100"', $response->content());
        self::assertStringContainsString('data-volt-document="spa"', $response->content());
        self::assertStringContainsString('data-volt-navigation-mode="auto"', $response->content());
        self::assertStringContainsString('data-volt-layout="routing-lab"', $response->content());
        self::assertStringContainsString('data-volt-hydrate="false"', $response->content());
        self::assertSame(1, substr_count($response->content(), 'data-volt-runtime="true"'));
        self::assertMatchesRegularExpression('/<script data-volt-runtime="true" src="\/_volt\/runtime\.js\?v=\d+" defer><\/script>/', $response->content());
    }

    public function test_routing_lab_user_screen_exposes_manifest_and_runtime_expectations(): void
    {
        $response = $this->handleSkeletonRequest('/routing-lab/users/15');
        $navigation = $this->handleSkeletonNavigationRequest('/routing-lab/users/15');
        $payload = $this->decodeNavigationPayload($navigation);

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Usuario 15', $response->content());
        self::assertStringContainsString('routing.lab.users.show', $response->content());
        self::assertStringContainsString('/_volt/routes-manifest.json', $response->content());
        self::assertStringContainsString('path = /routing-lab/users/{user}', $response->content());
        self::assertStringContainsString('data-volt-layout="routing-lab"', $response->content());
        self::assertStringContainsString('data-volt-runtime="true"', $response->content());

        self::assertSame(200, $navigation->statusCode(), $navigation->content());
        self::assertSame('/routing-lab/users/15', $payload['navigation']['target'] ?? null);
        self::assertSame('routing.lab.users.show', $payload['screen']['route'] ?? null);
        self::assertSame('spa', $payload['policy']['document'] ?? null);
        self::assertSame('auto', $payload['policy']['navigation'] ?? null);
        self::assertSame('routing-lab', $payload['runtime']['layout'] ?? null);
        self::assertSame('fade', $payload['runtime']['transition'] ?? null);
        self::assertTrue($payload['runtime']['hydrate'] ?? false);
    }

    public function test_routing_lab_login_screen_documents_redirect_contract(): void
    {
        $response = $this->handleSkeletonRequest('/login');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Login de prueba', $response->content());
        self::assertStringContainsString('redirect.location = /login', $response->content());
        self::assertStringContainsString('href="/routing-lab/private"', $response->content());
        self::assertStringContainsString('data-volt-layout="routing-lab"', $response->content());
    }

    public function test_routing_lab_error_route_emits_spa_navigation_error_payload(): void
    {
        $response = $this->handleSkeletonNavigationRequest('/routing-lab/boom');
        $payload = $this->decodeNavigationPayload($response);

        self::assertSame(500, $response->statusCode());
        self::assertSame('/routing-lab/boom', $payload['navigation']['target'] ?? null);
        self::assertSame('routing.lab.boom', $payload['screen']['route'] ?? null);
        self::assertNull($payload['redirect'] ?? null);
        self::assertSame([
            'code' => 500,
            'message' => 'Server Error',
        ], $payload['error'] ?? null);
        self::assertStringContainsString('<body data-volt-document="reload" data-volt-layout="routing-lab">', $response->content());
    }

    public function test_runtime_asset_exposes_runtime_hooks_and_public_apis(): void
    {
        $response = $this->handleSkeletonRequest('/routing-lab');
        $runtimeAsset = $this->handleSkeletonRequest('/_volt/runtime.js');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('/_volt/runtime.js?v=', $response->content());
        self::assertMatchesRegularExpression('/<script data-volt-runtime="true" src="\/_volt\/runtime\.js\?v=\d+" defer><\/script>/', $response->content());

        self::assertSame(200, $runtimeAsset->statusCode(), $runtimeAsset->content());
        self::assertSame('application/javascript; charset=UTF-8', $runtimeAsset->headers()['Content-Type']);
        self::assertStringContainsString('volt:request-finish', $runtimeAsset->content());
        self::assertStringContainsString('volt:component-destroyed', $runtimeAsset->content());
        self::assertStringContainsString('function cleanupRuntimeOrphans()', $runtimeAsset->content());
        self::assertStringContainsString('navigationViewportTrackedElements: new Set(),', $runtimeAsset->content());
        self::assertStringContainsString('window.Volt.components = createPublicComponentsApi();', $runtimeAsset->content());
        self::assertStringContainsString('window.Volt.telemetry = createPublicTelemetryApi();', $runtimeAsset->content());
    }

    public function test_runtime_source_reads_wrapped_component_document_meta_from_the_full_parsed_document(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $navigationSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '13-state-sync-navigation.js'
        );
        $runtimeAsset = $this->handleSkeletonRequest('/_volt/runtime.js');

        self::assertIsString($navigationSource);
        self::assertSame(200, $runtimeAsset->statusCode(), $runtimeAsset->content());
        self::assertStringContainsString('typeof doc.querySelector === "function"', $navigationSource);
        self::assertStringContainsString('? doc.querySelector(selector)', $navigationSource);
        self::assertStringContainsString('typeof doc.querySelector === "function"', $runtimeAsset->content());
        self::assertStringContainsString('? doc.querySelector(selector)', $runtimeAsset->content());
    }

    public function test_runtime_source_only_falls_back_for_layout_changes_when_both_documents_declare_layouts(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $navigationDocumentSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '42-navigation-document.js'
        );
        $runtimeAsset = $this->handleSkeletonRequest('/_volt/runtime.js');

        self::assertIsString($navigationDocumentSource);
        self::assertSame(200, $runtimeAsset->statusCode(), $runtimeAsset->content());
        self::assertStringContainsString('if (!currentLayout || !nextLayout) {', $navigationDocumentSource);
        self::assertStringContainsString('if (!currentLayout || !nextLayout) {', $runtimeAsset->content());
    }

    public function test_runtime_source_keeps_spa_navigation_on_get_and_protocol_actions_on_post(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $navigationSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '44-navigation-visit.js'
        );
        $actionSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '45-action-dispatch.js'
        );

        self::assertIsString($navigationSource);
        self::assertIsString($actionSource);
        self::assertStringContainsString('method: "GET"', $navigationSource);
        self::assertStringContainsString('"X-Volt-Navigate": "true"', $navigationSource);
        self::assertStringContainsString('method: "POST"', $actionSource);
        self::assertStringContainsString('"/_volt/action"', $actionSource);
    }

    public function test_runtime_source_exposes_redirect_as_an_explicit_navigation_payload_field(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $navigationCacheSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '20-navigation-cache.js'
        );
        $visitSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '44-navigation-visit.js'
        );

        self::assertIsString($navigationCacheSource);
        self::assertIsString($visitSource);
        self::assertStringContainsString('redirect: redirectTarget,', $navigationCacheSource);
        self::assertStringContainsString('redirect: responseRedirect,', $navigationCacheSource);
        self::assertStringContainsString('payload && payload.redirect', $visitSource);
    }

    public function test_runtime_source_exposes_error_as_an_explicit_navigation_payload_field(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $navigationCacheSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '20-navigation-cache.js'
        );
        $visitSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '44-navigation-visit.js'
        );

        self::assertIsString($navigationCacheSource);
        self::assertIsString($visitSource);
        self::assertStringContainsString('navigationErrorPayload(response.status, response.statusText)', $visitSource);
        self::assertStringContainsString('payload.error =', $visitSource);
        self::assertStringContainsString('if (payload && payload.error && typeof payload.error === "object") {', $visitSource);
        self::assertStringContainsString('if (payload && payload.error && typeof payload.error === "object") {', $navigationCacheSource);
        self::assertStringContainsString('error: payload.error,', $visitSource);
    }

    public function test_runtime_source_exposes_target_as_an_explicit_navigation_payload_field(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $navigationCacheSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '20-navigation-cache.js'
        );
        $visitSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '44-navigation-visit.js'
        );

        self::assertIsString($navigationCacheSource);
        self::assertIsString($visitSource);
        self::assertStringContainsString('target: entry.target || entry.url,', $navigationCacheSource);
        self::assertStringContainsString('target: payloadTarget,', $navigationCacheSource);
        self::assertStringContainsString('payload.target = normalizeNavigationUrl(spaNavigation.navigation.target);', $visitSource);
        self::assertStringContainsString('let navigationTarget = normalizedUrl;', $visitSource);
        self::assertStringContainsString('target: navigationTarget,', $visitSource);
    }

    private function handleSkeletonRequest(string $path): Response
    {
        $app = new Application(self::$skeletonBasePath);
        $bootstrapper = new Bootstrapper($app);
        $bootstrapper->loadConfiguration();

        foreach ((array) $app->config('app.providers', []) as $provider) {
            $app->register($provider);
        }

        $app->boot();

        $router = $app->make(Router::class);

        $routes = require self::$skeletonBasePath . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php';
        $routes($router);

        return $app->make(HttpKernel::class)->handle(Request::create($path));
    }

    private function handleSkeletonNavigationRequest(string $path): Response
    {
        $app = new Application(self::$skeletonBasePath);
        $bootstrapper = new Bootstrapper($app);
        $bootstrapper->loadConfiguration();

        foreach ((array) $app->config('app.providers', []) as $provider) {
            $app->register($provider);
        }

        $app->boot();

        $router = $app->make(Router::class);

        $routes = require self::$skeletonBasePath . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php';
        $routes($router);

        return $app->make(HttpKernel::class)->handle(Request::create(
            $path,
            'GET',
            [],
            [],
            [],
            [],
            [],
            [
                'HTTP_X_REQUESTED_WITH' => 'VoltStack',
                'HTTP_X_VOLT_NAVIGATE' => 'true',
            ],
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeNavigationPayload(Response $response): array
    {
        $payload = $response->headers()['X-Volt-Navigation'] ?? null;

        self::assertIsString($payload);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    private static function locateSkeletonBasePath(): string
    {
        $candidates = [
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'app-skeleton',
            dirname(__DIR__, 5),
        ];

        foreach ($candidates as $candidate) {
            if (
                is_file($candidate . DIRECTORY_SEPARATOR . 'composer.json') &&
                is_dir($candidate . DIRECTORY_SEPARATOR . 'app') &&
                is_dir($candidate . DIRECTORY_SEPARATOR . 'routes')
            ) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Unable to locate the app-skeleton base path for the integration tests.');
    }
}