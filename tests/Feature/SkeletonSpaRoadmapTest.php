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

    public function test_spa_reactive_entry_screen_exposes_component_navigation_targets(): void
    {
        $response = $this->handleSkeletonRequest('/spaReactive');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Sistema de Analisis de Runtime SPA Full Reactive', $response->content());
        self::assertStringContainsString('href="/counterExample"', $response->content());
        self::assertStringContainsString('href="/formExample"', $response->content());
        self::assertStringContainsString('href="/runtimeState"', $response->content());
        self::assertStringContainsString('data-volt-document="spa"', $response->content());
        self::assertStringContainsString('data-volt-navigation-mode="auto"', $response->content());
        self::assertStringContainsString('data-volt-layout="spa"', $response->content());
        self::assertSame(1, substr_count($response->content(), 'data-volt-runtime="true"'));
    }

    public function test_cache_example_screen_renders_declared_navigation_and_invalidation_sections(): void
    {
        $response = $this->handleSkeletonRequest('/cacheExample');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Runtime Cache Demo', $response->content());
        self::assertStringContainsString('Recarga controlada', $response->content());
        self::assertStringContainsString('volt:cache-hit', $response->content());
        self::assertStringContainsString("volt:navigation-cache-invalidate", $response->content());
    }

    public function test_cache_example_screen_includes_extra_built_assets_only_for_that_route(): void
    {
        $home = $this->handleSkeletonRequest('/');
        $cacheExample = $this->handleSkeletonRequest('/cacheExample');

        self::assertSame(200, $home->statusCode(), $home->content());
        self::assertSame(200, $cacheExample->statusCode(), $cacheExample->content());
        self::assertSame(1, substr_count($home->content(), '<script type="module" src="/build/assets/'));
        self::assertSame(1, substr_count($home->content(), '<link rel="stylesheet" href="/build/assets/'));
        self::assertGreaterThanOrEqual(2, substr_count($cacheExample->content(), '<script type="module" src="/build/assets/'));
        self::assertGreaterThanOrEqual(2, substr_count($cacheExample->content(), '<link rel="stylesheet" href="/build/assets/'));
    }

    public function test_fragment_cache_screen_exposes_preserve_targets_and_fragment_monitors(): void
    {
        $response = $this->handleSkeletonRequest('/fragmentCache');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Cache Demo', $response->content());
        self::assertStringContainsString('<meta name="volt-fragment-control" content="preserve"', $response->content());
        self::assertStringContainsString('data-volt-preserve="draft-fragment"', $response->content());
        self::assertStringContainsString('data-volt-preserve="live-shell"', $response->content());
        self::assertStringContainsString('volt:fragment-preserve', $response->content());
        self::assertStringContainsString('volt:fragment-discard', $response->content());
        self::assertStringContainsString('/formExample', $response->content());
        self::assertStringContainsString('/fragmentCacheReset', $response->content());
    }

    public function test_form_example_screen_exposes_matching_preserve_targets_for_reuse(): void
    {
        $response = $this->handleSkeletonRequest('/formExample');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('<meta name="volt-fragment-control" content="preserve"', $response->content());
        self::assertStringContainsString('data-volt-preserve="draft-fragment"', $response->content());
        self::assertStringContainsString('data-volt-preserve="live-shell"', $response->content());
        self::assertStringContainsString('volt:fragment-preserve', $response->content());
        self::assertStringContainsString('volt:fragment-discard', $response->content());
    }

    public function test_fragment_cache_reset_screen_declares_documental_reset_policy_for_preserved_fragments(): void
    {
        $response = $this->handleSkeletonRequest('/fragmentCacheReset');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Cache Reset', $response->content());
        self::assertStringContainsString('<meta name="volt-fragment-control" content="reset"', $response->content());
        self::assertStringContainsString('<meta name="volt-cache-control" content="no-store"', $response->content());
        self::assertStringContainsString('data-volt-preserve="draft-fragment"', $response->content());
        self::assertStringContainsString('data-volt-preserve="live-shell"', $response->content());
        self::assertStringContainsString('documento impide reutilizar el nodo anterior', $response->content());
    }

    public function test_fragment_cache_manual_validation_routes_expose_control_and_discard_expectations(): void
    {
        $origin = $this->handleSkeletonRequest('/fragmentCache');
        $compatibleTarget = $this->handleSkeletonRequest('/formExample');
        $resetTarget = $this->handleSkeletonRequest('/fragmentCacheReset');

        self::assertSame(200, $origin->statusCode(), $origin->content());
        self::assertSame(200, $compatibleTarget->statusCode(), $compatibleTarget->content());
        self::assertSame(200, $resetTarget->statusCode(), $resetTarget->content());

        self::assertStringContainsString('Este contenido deberia resetearse.', $origin->content());
        self::assertStringContainsString('Probar descarte en /fragmentCacheReset', $origin->content());
        self::assertStringContainsString('Este contenido deberia resetearse.', $compatibleTarget->content());
        self::assertStringContainsString('Volver a fragment cache', $compatibleTarget->content());
        self::assertStringContainsString('document-policy', $resetTarget->content());
        self::assertStringContainsString('impide reutilizar el nodo anterior', $resetTarget->content());
    }

    public function test_request_lab_screen_exposes_explicit_abort_and_stale_controls(): void
    {
        $response = $this->handleSkeletonRequest('/runtimeRequestLab');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Abort previous action', $response->content());
        self::assertStringContainsString('Abort previous navigation', $response->content());
        self::assertStringContainsString('Stale navigation', $response->content());
        self::assertStringContainsString('volt:request-abort', $response->content());
        self::assertStringContainsString('/runtimeRequestLabSlow', $response->content());
    }

    public function test_runtime_persist_origin_screen_exposes_persist_targets_and_status_panel(): void
    {
        $response = $this->handleSkeletonRequest('/runtimePersist');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Persist MVP', $response->content());
        self::assertStringContainsString('<meta name="volt-fragment-control" content="preserve"', $response->content());
        self::assertStringContainsString('window.__voltPersistDemoState = window.__voltPersistDemoState || {', $response->content());
        self::assertStringContainsString('window.__voltPersistDemoState.lastNavigatedDetail =', $response->content());
        self::assertStringContainsString('data-volt-persist="persist-sidebar"', $response->content());
        self::assertStringContainsString('volt:persist="persist-player"', $response->content());
        self::assertStringContainsString('data-volt-persist-status', $response->content());
        self::assertStringContainsString('/runtimePersistBridge', $response->content());
        self::assertStringContainsString('/runtimePersistAlt', $response->content());
    }

    public function test_runtime_persist_bridge_screen_documents_registry_survival_without_targets(): void
    {
        $response = $this->handleSkeletonRequest('/runtimePersistBridge');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Persist Bridge', $response->content());
        self::assertStringContainsString('<meta name="volt-fragment-control" content="preserve"', $response->content());
        self::assertStringContainsString('window.__voltPersistDemoState = window.__voltPersistDemoState || {', $response->content());
        self::assertStringContainsString('window.__voltPersistDemoState.lastNavigatedDetail =', $response->content());
        self::assertStringContainsString('persistentFragmentRegistrySize', $response->content());
        self::assertStringContainsString('persistedFragments', $response->content());
        self::assertStringContainsString('/runtimePersistAlt', $response->content());
    }

    public function test_runtime_persist_destination_screen_exposes_reinjection_targets(): void
    {
        $response = $this->handleSkeletonRequest('/runtimePersistAlt');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('Persist Destination', $response->content());
        self::assertStringContainsString('<meta name="volt-fragment-control" content="preserve"', $response->content());
        self::assertStringContainsString('window.__voltPersistDemoState.lastNavigatedDetail =', $response->content());
        self::assertStringContainsString('data-volt-persist="persist-sidebar"', $response->content());
        self::assertStringContainsString('data-volt-persist="persist-player"', $response->content());
        self::assertStringContainsString('persistedFragments &gt; 0', $response->content());
        self::assertStringContainsString('Registry size', $response->content());
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

    public function test_skeleton_html_resolves_built_manifest_assets_when_hot_reload_is_not_active(): void
    {
        $manifestPath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'public'
            . DIRECTORY_SEPARATOR . 'build'
            . DIRECTORY_SEPARATOR . '.vite'
            . DIRECTORY_SEPARATOR . 'manifest.json';
        $response = $this->handleSkeletonRequest('/');

        self::assertFileExists($manifestPath);
        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('<link rel="stylesheet" href="/build/assets/', $response->content());
        self::assertStringContainsString('<script type="module" src="/build/assets/', $response->content());
        self::assertStringNotContainsString('@vite/client', $response->content());
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

    public function test_runtime_source_can_preload_stylesheets_and_modules_from_prefetched_documents(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $cacheSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '20-navigation-cache.js'
        );
        $runtimeAsset = $this->handleSkeletonRequest('/_volt/runtime.js');

        self::assertIsString($cacheSource);
        self::assertSame(200, $runtimeAsset->statusCode(), $runtimeAsset->content());
        self::assertStringContainsString('key: "style:" + href,', $cacheSource);
        self::assertStringContainsString('rel: "preload",', $cacheSource);
        self::assertStringContainsString('as: "style",', $cacheSource);
        self::assertStringContainsString('key: "style:" + href,', $runtimeAsset->content());
        self::assertStringContainsString('rel: "preload",', $runtimeAsset->content());
        self::assertStringContainsString('as: "style",', $runtimeAsset->content());
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

    public function test_runtime_source_handles_popstate_with_a_spa_visit_and_reload_fallback(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $bootSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '50-events-and-boot.js'
        );
        $runtimeAsset = $this->handleSkeletonRequest('/_volt/runtime.js');

        self::assertIsString($bootSource);
        self::assertSame(200, $runtimeAsset->statusCode(), $runtimeAsset->content());
        self::assertStringContainsString('window.addEventListener("popstate", function () {', $bootSource);
        self::assertStringContainsString('visit(window.location.href, {', $bootSource);
        self::assertStringContainsString('updateHistory: false,', $bootSource);
        self::assertStringContainsString('historyMode: "replace",', $bootSource);
        self::assertStringContainsString('preserveScroll: false,', $bootSource);
        self::assertStringContainsString('fallback: false,', $bootSource);
        self::assertStringContainsString('window.location.reload();', $bootSource);
        self::assertStringContainsString('window.addEventListener("popstate", function () {', $runtimeAsset->content());
        self::assertStringContainsString('visit(window.location.href, {', $runtimeAsset->content());
        self::assertStringContainsString('window.location.reload();', $runtimeAsset->content());
    }

    public function test_runtime_source_reconciles_managed_head_entries_without_duplicating_scripts(): void
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
        self::assertStringContainsString('function managedHeadNodeKey(node) {', $navigationDocumentSource);
        self::assertStringContainsString('return "script:" + (node.getAttribute("type") || "") + ":" + src;', $navigationDocumentSource);
        self::assertStringContainsString('async function reconcileDocumentHead(nextHead) {', $navigationDocumentSource);
        self::assertStringContainsString('const existing = currentMap.get(entry.key);', $navigationDocumentSource);
        self::assertStringContainsString('syncManagedHeadNode(existing, entry.node);', $navigationDocumentSource);
        self::assertStringContainsString('const clone = entry.node.cloneNode(true);', $navigationDocumentSource);
        self::assertStringContainsString('document.head.appendChild(clone);', $navigationDocumentSource);
        self::assertStringContainsString('return "script:" + (node.getAttribute("type") || "") + ":" + src;', $runtimeAsset->content());
        self::assertStringContainsString('async function reconcileDocumentHead(nextHead) {', $runtimeAsset->content());
        self::assertStringContainsString('syncManagedHeadNode(existing, entry.node);', $runtimeAsset->content());
    }

    public function test_runtime_source_exposes_preserved_fragment_capture_restore_and_discard_contract(): void
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
        $navigationStateSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '13-state-sync-navigation.js'
        );
        $runtimeAsset = $this->handleSkeletonRequest('/_volt/runtime.js');

        self::assertIsString($navigationDocumentSource);
        self::assertIsString($navigationStateSource);
        self::assertSame(200, $runtimeAsset->statusCode(), $runtimeAsset->content());
        self::assertStringContainsString('function preservedFragmentAttribute(element) {', $navigationDocumentSource);
        self::assertStringContainsString('"data-volt-preserve",', $navigationDocumentSource);
        self::assertStringContainsString('"volt-preserve",', $navigationDocumentSource);
        self::assertStringContainsString('"volt:preserve",', $navigationDocumentSource);
        self::assertStringContainsString('function capturePreservedFragments(root, meta) {', $navigationDocumentSource);
        self::assertStringContainsString('function restorePreservedFragments(root, fragments, meta) {', $navigationDocumentSource);
        self::assertStringContainsString('"volt:fragment-preserve",', $navigationDocumentSource);
        self::assertStringContainsString('"volt:fragment-discard",', $navigationDocumentSource);
        self::assertStringContainsString('function fragmentControlForDocument(doc) {', $navigationStateSource);
        self::assertStringContainsString('control.mode = "reset";', $navigationStateSource);
        self::assertStringContainsString('const declaredMeta = firstDocumentMetaValue(', $navigationStateSource);
        self::assertStringContainsString('function preservedFragmentAttribute(element) {', $runtimeAsset->content());
        self::assertStringContainsString('"volt:fragment-preserve",', $runtimeAsset->content());
        self::assertStringContainsString('"volt:fragment-discard",', $runtimeAsset->content());
        self::assertStringContainsString('function fragmentControlForDocument(doc) {', $runtimeAsset->content());
    }

    public function test_runtime_source_exposes_persistent_fragment_capture_and_restore_contract(): void
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
        self::assertStringContainsString('function persistedFragmentAttribute(element) {', $navigationDocumentSource);
        self::assertStringContainsString('"data-volt-persist",', $navigationDocumentSource);
        self::assertStringContainsString('"volt-persist",', $navigationDocumentSource);
        self::assertStringContainsString('"volt:persist",', $navigationDocumentSource);
        self::assertStringContainsString('function persistedFragmentKey(element) {', $navigationDocumentSource);
        self::assertStringContainsString('runtime.persistentFragments.set(key, fragment);', $navigationDocumentSource);
        self::assertStringContainsString('const targets = persistentFragmentTargets(root);', $navigationDocumentSource);
        self::assertStringContainsString('runtime.persistentFragments.delete(key);', $navigationDocumentSource);
        self::assertStringContainsString('persistentRegistrySize: runtime.persistentFragments.size,', $navigationDocumentSource);
        self::assertStringContainsString('function persistedFragmentAttribute(element) {', $runtimeAsset->content());
        self::assertStringContainsString('"volt:persist",', $runtimeAsset->content());
        self::assertStringContainsString('runtime.persistentFragments.set(key, fragment);', $runtimeAsset->content());
        self::assertStringContainsString('persistentRegistrySize: runtime.persistentFragments.size,', $runtimeAsset->content());
    }

    public function test_runtime_source_falls_back_to_full_reload_when_navigation_returns_http_errors(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $visitSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '44-navigation-visit.js'
        );
        $runtimeAsset = $this->handleSkeletonRequest('/_volt/runtime.js');

        self::assertIsString($visitSource);
        self::assertSame(200, $runtimeAsset->statusCode(), $runtimeAsset->content());
        self::assertStringContainsString('if (payload && payload.error && typeof payload.error === "object") {', $visitSource);
        self::assertStringContainsString('fallbackReason = settings.fallback !== false ? "request-error" : null;', $visitSource);
        self::assertStringContainsString('emitRuntimeHook("volt:request-error", errorDetail, document);', $visitSource);
        self::assertStringContainsString('window.location.assign(finalUrl);', $visitSource);
        self::assertStringContainsString('window.location.assign(normalizedUrl);', $visitSource);
        self::assertStringContainsString('if (payload && payload.error && typeof payload.error === "object") {', $runtimeAsset->content());
        self::assertStringContainsString('emitRuntimeHook("volt:request-error", errorDetail, document);', $runtimeAsset->content());
        self::assertStringContainsString('window.location.assign(finalUrl);', $runtimeAsset->content());
    }

    public function test_runtime_source_updates_snapshot_when_volt_model_inputs_change(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $bootSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '50-events-and-boot.js'
        );
        $runtimeAsset = $this->handleSkeletonRequest('/_volt/runtime.js');

        self::assertIsString($bootSource);
        self::assertSame(200, $runtimeAsset->statusCode(), $runtimeAsset->content());
        self::assertStringContainsString('const snapshot = readSnapshot(root);', $bootSource);
        self::assertStringContainsString('const key = directiveValue(element, ["volt-model", "volt:model"]);', $bootSource);
        self::assertStringContainsString('if (snapshot && snapshot.state && key) {', $bootSource);
        self::assertStringContainsString('snapshot.state[key] =', $bootSource);
        self::assertStringContainsString('root.setAttribute("data-volt-snapshot", JSON.stringify(snapshot));', $bootSource);
        self::assertStringContainsString('updateModelSyncDirectiveFromElement(element, root, "directive:model.sync:input");', $bootSource);
        self::assertStringContainsString('const snapshot = readSnapshot(root);', $runtimeAsset->content());
        self::assertStringContainsString('root.setAttribute("data-volt-snapshot", JSON.stringify(snapshot));', $runtimeAsset->content());
    }

    public function test_runtime_source_schedules_internal_sync_requests_for_volt_model_sync(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $modelDirectiveSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '11-dom-model-directives.js'
        );
        $runtimeAsset = $this->handleSkeletonRequest('/_volt/runtime.js');

        self::assertIsString($modelDirectiveSource);
        self::assertSame(200, $runtimeAsset->statusCode(), $runtimeAsset->content());
        self::assertStringContainsString('function scheduleModelSyncDirectiveDispatch(root, element) {', $modelDirectiveSource);
        self::assertStringContainsString('MODEL_SYNC_INTERNAL_ACTION,', $modelDirectiveSource);
        self::assertStringContainsString('}, MODEL_SYNC_DEBOUNCE);', $modelDirectiveSource);
        self::assertStringContainsString('runtime.modelSyncDebounces.set(element, timeoutId);', $modelDirectiveSource);
        self::assertStringContainsString('runtime.modelSyncTrackedElements.add(element);', $modelDirectiveSource);
        self::assertStringContainsString('function scheduleModelSyncDirectiveDispatch(root, element) {', $runtimeAsset->content());
        self::assertStringContainsString('MODEL_SYNC_INTERNAL_ACTION,', $runtimeAsset->content());
        self::assertStringContainsString('runtime.modelSyncTrackedElements.add(element);', $runtimeAsset->content());
    }

    public function test_runtime_source_updates_snapshot_after_action_responses_and_emits_stale_abort_hooks(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $actionSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '45-action-dispatch.js'
        );
        $runtimeAsset = $this->handleSkeletonRequest('/_volt/runtime.js');

        self::assertIsString($actionSource);
        self::assertSame(200, $runtimeAsset->statusCode(), $runtimeAsset->content());
        self::assertStringContainsString('outcome = "stale";', $actionSource);
        self::assertStringContainsString('"volt:request-stale",', $actionSource);
        self::assertStringContainsString('outcome = "aborted";', $actionSource);
        self::assertStringContainsString('"volt:request-abort",', $actionSource);
        self::assertStringContainsString('if (payload.snapshot && updatedRoot) {', $actionSource);
        self::assertStringContainsString('"data-volt-snapshot",', $actionSource);
        self::assertStringContainsString('JSON.stringify(payload.snapshot),', $actionSource);
        self::assertStringContainsString('"volt:request-stale",', $runtimeAsset->content());
        self::assertStringContainsString('"volt:request-abort",', $runtimeAsset->content());
        self::assertStringContainsString('JSON.stringify(payload.snapshot),', $runtimeAsset->content());
    }

    public function test_runtime_source_keeps_reactive_actions_without_automatic_retry(): void
    {
        $frameworkBasePath = self::$skeletonBasePath
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'voltstack'
            . DIRECTORY_SEPARATOR . 'framework';

        $actionSource = file_get_contents(
            $frameworkBasePath
            . DIRECTORY_SEPARATOR . 'frontend'
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . '45-action-dispatch.js'
        );
        $runtimeAsset = $this->handleSkeletonRequest('/_volt/runtime.js');

        self::assertIsString($actionSource);
        self::assertSame(200, $runtimeAsset->statusCode(), $runtimeAsset->content());
        self::assertStringContainsString('const response = await withRequestTimeout(', $actionSource);
        self::assertStringNotContainsString('resolveRequestRetryPolicy("action"', $actionSource);
        self::assertStringNotContainsString('"volt:request-retry"', $actionSource);
        self::assertStringNotContainsString('waitForRetryDelay(', $actionSource);
        self::assertStringContainsString('const response = await withRequestTimeout(', $runtimeAsset->content());
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
