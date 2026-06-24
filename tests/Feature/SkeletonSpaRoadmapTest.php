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

    public function test_fragment_cache_routes_expose_preserve_contract_for_reuse_targets(): void
    {
        $fragmentCache = $this->handleSkeletonRequest('/fragmentCache');
        $formExample = $this->handleSkeletonRequest('/formExample');

        self::assertSame(200, $fragmentCache->statusCode(), $fragmentCache->content());
        self::assertSame(200, $formExample->statusCode(), $formExample->content());

        self::assertStringContainsString(
            '<meta name="volt-fragment-control" content="preserve" data-volt-head-key="fragment-control-preserve">',
            $fragmentCache->content(),
        );
        self::assertStringContainsString('data-volt-preserve="draft-fragment"', $fragmentCache->content());
        self::assertStringContainsString('data-volt-preserve="live-shell"', $fragmentCache->content());
        self::assertStringContainsString('data-volt-preserve="draft-fragment"', $formExample->content());
        self::assertStringContainsString('data-volt-preserve="live-shell"', $formExample->content());
    }

    public function test_fragment_cache_reset_and_navigation_policy_routes_emit_expected_spa_contract_markers(): void
    {
        $reset = $this->handleSkeletonRequest('/fragmentCacheReset');
        $policy = $this->handleSkeletonRequest('/navigationPolicy');
        $documentReload = $this->handleSkeletonRequest('/navigationDocumentReload');

        self::assertSame(200, $reset->statusCode(), $reset->content());
        self::assertStringContainsString(
            '<meta name="volt-cache-control" content="no-store" data-volt-head-key="fragment-cache-no-store">',
            $reset->content(),
        );
        self::assertStringContainsString(
            '<meta name="volt-fragment-control" content="reset" data-volt-head-key="fragment-control-reset">',
            $reset->content(),
        );

        self::assertSame(200, $policy->statusCode(), $policy->content());
        self::assertStringContainsString(
            '<meta name="volt-navigation-mode" content="auto" data-volt-head-key="navigation-mode-auto">',
            $policy->content(),
        );
        self::assertStringContainsString('data-runtime-check="policy-link-spa"', $policy->content());
        self::assertStringContainsString('volt:navigate="reload" volt:prefetch="none"', $policy->content());
        self::assertStringContainsString('data-runtime-check="policy-link-reload"', $policy->content());
        self::assertStringContainsString(
            'href="/navigationDocumentReload" volt:navigate volt:prefetch="none" volt:cache="no-store"',
            $policy->content(),
        );
        self::assertStringContainsString('data-runtime-check="policy-link-document-reload"', $policy->content());
        self::assertStringContainsString('data-runtime-check="navigation-arrival-panel"', $policy->content());
        self::assertStringContainsString('data-runtime-check="navigation-arrival-kind"', $policy->content());
        self::assertStringContainsString('data-runtime-check="navigation-arrival-summary"', $policy->content());
        self::assertStringContainsString('data-runtime-check="navigation-arrival-detail"', $policy->content());

        self::assertSame(200, $documentReload->statusCode(), $documentReload->content());
        self::assertStringContainsString(
            '<meta name="volt-document" content="reload" data-volt-head-key="navigation-document-reload">',
            $documentReload->content(),
        );
        self::assertStringContainsString(
            '<meta name="volt-navigation-mode" content="reload" data-volt-head-key="navigation-mode-reload">',
            $documentReload->content(),
        );
        self::assertStringContainsString('data-runtime-check="document-reload-request-marker"', $documentReload->content());
        self::assertStringContainsString('data-runtime-check="document-reload-back-to-lab"', $documentReload->content());
        self::assertStringContainsString('<body data-volt-document="spa" data-volt-navigation-mode="auto" data-volt-layout="app"', $documentReload->content());
    }

    public function test_skeleton_layout_emits_stable_head_and_layout_markers_for_spa_navigation(): void
    {
        $response = $this->handleSkeletonRequest('/fragmentCache');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('<meta charset="UTF-8" data-volt-head-key="document-charset">', $response->content());
        self::assertStringContainsString(
            '<meta name="viewport" content="width=device-width, initial-scale=1.0" data-volt-head-key="document-viewport">',
            $response->content(),
        );
        self::assertStringContainsString('<body data-volt-document="spa" data-volt-navigation-mode="auto" data-volt-layout="app"', $response->content());
        self::assertSame(1, substr_count($response->content(), 'data-volt-runtime="true"'));
    }

    public function test_runtime_model_sync_demo_exposes_selective_state_sync_contract_markers(): void
    {
        $response = $this->handleSkeletonRequest('/runtimeModelSync');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString(
            '<meta name="volt-navigation-mode" content="auto" data-volt-head-key="runtime-model-sync-mode">',
            $response->content(),
        );
        self::assertStringContainsString(
            'name="serverTitle" volt:model.sync="client:sync.title"',
            $response->content(),
        );
        self::assertStringContainsString(
            'name="serverBody" volt:model.sync="client:sync.body"',
            $response->content(),
        );
        self::assertStringContainsString(
            'name="serverCategory" volt:model.sync="shared:sync.category"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-volt-state-sync="client:sync.alias->updates.serverAliasMirror"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:loading="__volt_sync__"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:success="__volt_sync__"',
            $response->content(),
        );
    }

    public function test_runtime_model_sync_alt_demo_exposes_client_reset_and_shared_scope_contract_markers(): void
    {
        $response = $this->handleSkeletonRequest('/runtimeModelSyncAlt');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString(
            '<meta name="volt-navigation-mode" content="auto" data-volt-head-key="runtime-model-sync-alt-mode">',
            $response->content(),
        );
        self::assertStringContainsString(
            'name="serverTitle" volt:model.sync="client:sync.title"',
            $response->content(),
        );
        self::assertStringContainsString(
            'name="serverEnabled" volt:model.sync="shared:sync.enabled"',
            $response->content(),
        );
        self::assertStringContainsString(
            'name="serverCategory" volt:model.sync="shared:sync.category"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-volt-state-sync="client:sync.alias->updates.serverAliasMirror"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-client-title-field"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-shared-enabled-field"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-shared-category-field"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-alias-field"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-client-title-store"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-shared-enabled-store"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-shared-category-store"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-client-alias-store"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-server-title"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-server-enabled"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-server-category"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="runtime-model-sync-alt-server-alias"',
            $response->content(),
        );
    }

    public function test_runtime_advanced_directives_demo_exposes_compound_expression_contract_markers(): void
    {
        $response = $this->handleSkeletonRequest('/runtimeAdvancedDirectives');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString(
            '<meta name="volt-navigation-mode" content="auto" data-volt-head-key="runtime-advanced-directives-mode">',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:text="client:draft.note ?? shared:draft.note ?? \'Sin nota disponible\'"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:show="client:ui.showClientPanel && !shared:ui.showSharedPanel"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:if="shared:ui.mountSharedPanel || (client:ui.mountClientPanel && !shared:ui.showSharedPanel)"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:class="client:ui.highlightClientCard && !shared:ui.lockSharedAction -> ring-4 ring-cyan-400 shadow-lg shadow-cyan-950/40 | shared:ui.highlightSharedCard -> -translate-y-1 shadow-xl shadow-fuchsia-950/30"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:attr="client:ui.lockClientAction && !shared:ui.lockSharedAction -> disabled=disabled, aria-disabled=true, data-lock=client-only | shared:ui.lockSharedAction -> disabled=disabled, aria-disabled=true, data-lock=shared, title=Bloqueado por shared"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:style="client:ui.softenClientCard && !shared:ui.softenSharedCard -> opacity:0.55; transform:scale(0.98) translateY(6px) | shared:ui.softenSharedCard -> opacity:0.85; box-shadow:0 18px 40px rgba(217,70,239,0.22); outline:1px solid rgba(217,70,239,0.4)"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-volt-state-action="preset-text-shared-fallback"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-volt-state-action="preset-multi-rule-shared"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-preset-status',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="text-fallback-result"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="show-compound-panel"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="if-compound-panel"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="class-multi-card"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="attr-multi-button"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="style-multi-card"',
            $response->content(),
        );
    }

    public function test_runtime_advanced_directives_demo_exposes_relational_and_null_undefined_examples(): void
    {
        $response = $this->handleSkeletonRequest('/runtimeAdvancedDirectives');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString(
            'volt:show="client:counter >= 2 && shared:counter < 3"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:show="client:counter >= shared:counter"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:if="shared:draft.note == \'activar\' || client:draft.note != \'pausa\'"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:if="shared:draft.note === \'activar\' || client:draft.note !== \'pausa\'"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:show="client:edge.nullValue == shared:edge.undefinedValue"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:show="client:edge.nullValue === shared:edge.undefinedValue"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:show="client:edge.undefinedValue == shared:edge.nullValue"',
            $response->content(),
        );
        self::assertStringContainsString(
            'volt:show="shared:edge.undefinedValue == null && shared:edge.undefinedValue !== null"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="relational-threshold-panel"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="relational-ref-panel"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="null-undefined-flex-panel"',
            $response->content(),
        );
        self::assertStringContainsString(
            'data-runtime-check="null-undefined-strict-panel"',
            $response->content(),
        );
    }

    public function test_runtime_events_demo_exposes_runtime_hook_lab_and_public_runtime_apis(): void
    {
        $response = $this->handleSkeletonRequest('/runtimeEvents');

        self::assertSame(200, $response->statusCode(), $response->content());
        self::assertStringContainsString('data-runtime-events-demo', $response->content());
        self::assertStringContainsString('volt:request-finish', $response->content());
        self::assertStringContainsString('volt:component-destroyed', $response->content());
        self::assertStringContainsString('function cleanupRuntimeOrphans()', $response->content());
        self::assertStringContainsString('navigationViewportTrackedElements: new Set(),', $response->content());
        self::assertStringContainsString('window.Volt.components = createPublicComponentsApi();', $response->content());
        self::assertStringContainsString('window.Volt.telemetry = createPublicTelemetryApi();', $response->content());
        self::assertStringContainsString('window.Volt.state ? window.Volt.state : null;', $response->content());
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
