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
        self::assertStringContainsString('volt:navigate="reload" volt:prefetch="none"', $policy->content());
        self::assertStringContainsString(
            'href="/navigationDocumentReload" volt:navigate volt:prefetch="none" volt:cache="no-store"',
            $policy->content(),
        );

        self::assertSame(200, $documentReload->statusCode(), $documentReload->content());
        self::assertStringContainsString(
            '<meta name="volt-document" content="reload" data-volt-head-key="navigation-document-reload">',
            $documentReload->content(),
        );
        self::assertStringContainsString(
            '<meta name="volt-navigation-mode" content="reload" data-volt-head-key="navigation-mode-reload">',
            $documentReload->content(),
        );
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
