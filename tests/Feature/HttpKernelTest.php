<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Config\ConfigRepository;
use Quantum\Actions\Action;
use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use Quantum\HttpKernel\HttpKernel;
use Quantum\Routing\CollectionArtifactStore;
use Quantum\Routing\Exceptions\DuplicateRouteException;
use Quantum\Routing\Exceptions\DuplicateRouteNameException;
use Quantum\Routing\Exceptions\RouteUrlGenerationException;
use Quantum\Routing\MetadataArtifactStore;
use Quantum\Routing\PipelineArtifactStore;
use Quantum\Routing\Router;
use Quantum\Routing\TreeArtifactStore;
use Quantum\Routing\VersionArtifactStore;
use VoltStack\Framework\Application;

final class HttpKernelTest extends TestCase
{
    private string $basePath;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-http-kernel-' . uniqid('', true);

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

    public function test_it_dispatches_string_controller_actions(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/controller-string', TestStringController::class . '@show');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/controller-string'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('controller-string', $response->content());
    }

    public function test_it_dispatches_controller_routes_without_reading_php_attributes_in_live_runtime(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/controller-attributes-live/{id}', TestAttributedController::class . '@show');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/controller-attributes-live/42'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('attributes:42', $response->content());
    }

    public function test_it_dispatches_action_routes(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/actions/{id}', TestShowUserAction::class);

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/actions/42'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('action:42:/actions/42', $response->content());
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

    public function test_it_runs_route_middlewares_around_the_endpoint_dispatcher(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/route-middleware', fn() => new Response('ok'))
            ->middleware(TestHeaderMiddleware::class);

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/route-middleware'));

        self::assertSame('passed', $response->headers()['X-Middleware']);
    }

    public function test_it_executes_global_and_route_middlewares_in_stable_order(): void
    {
        TestExecutionTrace::reset();

        $router = $this->app->make(Router::class);
        $router->get('/pipeline-order', function (): string {
            TestExecutionTrace::push('action');

            return 'ok';
        })->middleware([
            TestRouteTraceMiddleware::class,
            static function (Request $request, \Closure $next): mixed {
                TestExecutionTrace::push('route-closure-before');
                $response = $next($request);
                TestExecutionTrace::push('route-closure-after');

                return $response;
            },
        ]);

        $kernel = $this->app->make(HttpKernel::class);
        $kernel->setMiddlewares([TestGlobalTraceMiddleware::class]);
        $kernel->handle(Request::create('/pipeline-order'));

        self::assertSame([
            'global-before',
            'route-before',
            'route-closure-before',
            'action',
            'route-closure-after',
            'route-after',
            'global-after',
        ], TestExecutionTrace::all());
    }

    public function test_it_applies_group_middlewares_to_registered_routes(): void
    {
        $router = $this->app->make(Router::class);
        $router->group(['middleware' => TestHeaderMiddleware::class], function (Router $router): void {
            $router->get('/group-middleware', fn() => new Response('ok'));
        });

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/group-middleware'));

        self::assertSame('passed', $response->headers()['X-Middleware']);
    }

    public function test_it_composes_nested_group_middlewares_prefixes_and_route_middlewares_in_stable_order(): void
    {
        TestExecutionTrace::reset();

        $router = $this->app->make(Router::class);
        $router->group([
            'prefix' => '/api',
            'middleware' => TestOuterGroupTraceMiddleware::class,
        ], function (Router $router): void {
            $router->group([
                'prefix' => '/v1',
                'middleware' => TestInnerGroupTraceMiddleware::class,
            ], function (Router $router): void {
                $router->get('/users', function (): string {
                    TestExecutionTrace::push('action');

                    return 'ok';
                })->middleware(TestRouteTraceMiddleware::class);
            });
        });

        $kernel = $this->app->make(HttpKernel::class);
        $kernel->setMiddlewares([TestGlobalTraceMiddleware::class]);
        $response = $kernel->handle(Request::create('/api/v1/users'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('ok', $response->content());
        self::assertSame([
            'global-before',
            'group-outer-before',
            'group-inner-before',
            'route-before',
            'action',
            'route-after',
            'group-inner-after',
            'group-outer-after',
            'global-after',
        ], TestExecutionTrace::all());
    }

    public function test_it_can_apply_group_domains_to_routes(): void
    {
        $router = $this->app->make(Router::class);
        $router->group(['domain' => 'admin.example.com'], function (Router $router): void {
            $router->get('/group-domain', fn() => 'admin');
        });

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/group-domain', 'GET', [], [], [], [], [], [
            'HTTP_HOST' => 'admin.example.com',
        ]));

        self::assertSame(200, $response->statusCode());
        self::assertSame('admin', $response->content());
    }

    public function test_it_resolves_global_middleware_aliases_before_runtime(): void
    {
        $kernel = $this->app->make(HttpKernel::class);
        $kernel->aliasMiddleware('header', TestHeaderMiddleware::class);

        $router = $this->app->make(Router::class);
        $router->get('/global-alias', fn() => new Response('ok'));

        $kernel->setMiddlewares(['header']);
        $response = $kernel->handle(Request::create('/global-alias'));

        self::assertSame('passed', $response->headers()['X-Middleware']);
    }

    public function test_it_compiles_the_global_pipeline_when_middlewares_are_registered(): void
    {
        $kernel = $this->app->make(HttpKernel::class);
        $kernel->aliasMiddleware('header', TestHeaderMiddleware::class);
        $kernel->setMiddlewares(['header', TestHeaderMiddleware::class]);

        self::assertSame([TestHeaderMiddleware::class], $kernel->compiledMiddlewarePipeline()->middlewares());
        self::assertNotSame('', $kernel->compiledMiddlewarePipeline()->id());
    }

    public function test_it_resolves_group_middleware_aliases_before_runtime(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('header', TestHeaderMiddleware::class);
        $router->group(['middleware' => 'header'], function (Router $router): void {
            $router->get('/group-alias', fn() => new Response('ok'));
        });

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/group-alias'));

        self::assertSame('passed', $response->headers()['X-Middleware']);
    }

    public function test_it_resolves_route_middleware_aliases_before_runtime(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('header', TestHeaderMiddleware::class);
        $router->get('/route-alias', fn() => new Response('ok'))->middleware('header');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/route-alias'));

        self::assertSame('passed', $response->headers()['X-Middleware']);
    }

    public function test_it_can_dispatch_routes_using_loaded_pipeline_artifacts(): void
    {
        $router = $this->app->make(Router::class);
        $route = $router->get('/artifact-pipeline', fn() => new Response('ok'))->middleware(TestHeaderMiddleware::class);

        $this->app->make(PipelineArtifactStore::class)->compileAndWrite($router);
        $router->reloadPipelineArtifacts();

        self::assertNotSame($route->routePipeline(), $router->resolvedRoutePipeline($route));

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/artifact-pipeline'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('passed', $response->headers()['X-Middleware']);
    }

    public function test_it_can_dispatch_routes_using_a_loaded_collection_artifact(): void
    {
        $router = $this->app->make(Router::class);
        $route = $router->get('/artifact-collection', TestStringController::class . '@show')->name('artifact.collection');

        $this->app->make(CollectionArtifactStore::class)->compileAndWrite($router);
        $router->reloadCollectionArtifacts();

        $compiledRoute = $router->compiledCollection()->named('artifact.collection');

        self::assertNotNull($compiledRoute);
        self::assertNotSame($route, $compiledRoute);
        self::assertSame(TestStringController::class . '@show', $compiledRoute->action());

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/artifact-collection'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('controller-string', $response->content());
    }

    public function test_it_dispatches_collection_artifact_routes_without_reading_php_attributes_in_runtime(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/artifact-attributes/{id}', TestAttributedController::class . '@show')->name('artifact.attributes');

        $this->app->make(CollectionArtifactStore::class)->compileAndWrite($router);
        $router->reloadCollectionArtifacts();

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/artifact-attributes/99'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('attributes:99', $response->content());
    }

    public function test_it_can_match_requests_using_a_loaded_tree_artifact(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/artifact-tree/head', TestStringController::class . '@show')->name('artifact.tree.head');
        $router->post('/artifact-tree/submit/{id}', TestStringController::class . '@show')
            ->name('artifact.tree.submit')
            ->whereNumber('id');

        $this->app->make(CollectionArtifactStore::class)->compileAndWrite($router);
        $this->app->make(TreeArtifactStore::class)->compileAndWrite($router);
        $router->reloadCollectionArtifacts();

        $headResponse = $this->app->make(HttpKernel::class)->handle(Request::create('/artifact-tree/head', 'HEAD'));

        self::assertSame(200, $headResponse->statusCode());
        self::assertSame('', $headResponse->content());

        $methodResponse = $this->app->make(HttpKernel::class)->handle(Request::create('/artifact-tree/submit/42', 'GET'));

        self::assertSame(405, $methodResponse->statusCode());
        self::assertSame('POST, OPTIONS', $methodResponse->headers()['Allow']);

        $optionsResponse = $this->app->make(HttpKernel::class)->handle(Request::create('/artifact-tree/submit/42', 'OPTIONS'));

        self::assertSame(204, $optionsResponse->statusCode());
        self::assertSame('POST, OPTIONS', $optionsResponse->headers()['Allow']);
    }

    public function test_it_can_apply_loaded_metadata_artifacts_over_collection_metadata(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/artifact-metadata', TestMetadataEchoController::class . '@show')
            ->name('artifact.metadata')
            ->meta([
                'auth' => 'session',
                'runtime' => ['mode' => 'spa'],
                'csrf' => true,
                'guest' => true,
            ]);

        $this->app->make(CollectionArtifactStore::class)->compileAndWrite($router);
        $this->app->make(MetadataArtifactStore::class)->compileAndWrite($router);
        $this->clearCollectionArtifactMetadata('artifact.metadata');

        $router->reloadCollectionArtifacts();

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/artifact-metadata'));
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->statusCode());
        self::assertSame('session', $payload['auth']);
        self::assertSame(['mode' => 'spa'], $payload['runtime']);
        self::assertTrue($payload['csrf']);
        self::assertTrue($payload['guest']);
        self::assertSame('artifact.metadata', $payload['all']['name']);
        self::assertSame(['GET'], $payload['all']['methods']);
    }

    public function test_it_falls_back_to_live_routes_when_the_version_artifact_detects_a_checksum_mismatch(): void
    {
        $router = $this->app->make(Router::class);
        $route = $router->get('/artifact-version', TestStringController::class . '@show')->name('artifact.version');

        $this->app->make(CollectionArtifactStore::class)->compileAndWrite($router);
        $this->app->make(TreeArtifactStore::class)->compileAndWrite($router);
        $this->app->make(MetadataArtifactStore::class)->compileAndWrite($router);
        $this->app->make(PipelineArtifactStore::class)->compileAndWrite($router);
        $this->app->make(VersionArtifactStore::class)->compileAndWrite($router);

        $collectionPath = $this->app->cachePath('routes/collection.php');
        $collectionContents = file_get_contents($collectionPath);

        if (! is_string($collectionContents)) {
            self::fail('Collection artifact contents could not be read.');
        }

        file_put_contents($collectionPath, $collectionContents . "\n");
        $router->reloadCollectionArtifacts();

        $compiledRoute = $router->compiledCollection()->named('artifact.version');

        self::assertSame($route, $compiledRoute);

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/artifact-version'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('controller-string', $response->content());
    }

    public function test_it_invalidates_routing_artifacts_automatically_in_development(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);
        $config->set('app.env', 'local');

        $router = $this->app->make(Router::class);
        $route = $router->get('/artifact-dev', TestResponseController::class . '@show')->name('artifact.dev')->middleware(TestHeaderMiddleware::class);

        $this->app->make(CollectionArtifactStore::class)->compileAndWrite($router);
        $this->app->make(TreeArtifactStore::class)->compileAndWrite($router);
        $this->app->make(MetadataArtifactStore::class)->compileAndWrite($router);
        $this->app->make(PipelineArtifactStore::class)->compileAndWrite($router);
        $this->app->make(VersionArtifactStore::class)->compileAndWrite($router);

        foreach ($this->routingArtifactPaths() as $path) {
            self::assertFileExists($path);
        }

        $router->reloadCollectionArtifacts();
        $router->reloadPipelineArtifacts();

        $compiledRoute = $router->compiledCollection()->named('artifact.dev');

        self::assertSame($route, $compiledRoute);
        self::assertSame($route->routePipeline(), $router->resolvedRoutePipeline($route));

        foreach ($this->routingArtifactPaths() as $path) {
            self::assertFileDoesNotExist($path);
        }

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/artifact-dev'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('passed', $response->headers()['X-Middleware']);
    }

    public function test_it_uses_routing_artifacts_automatically_in_production_without_manual_reload(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);
        $config->set('app.env', 'production');

        $router = $this->app->make(Router::class);
        $route = $router->get('/artifact-production', TestResponseController::class . '@show')
            ->name('artifact.production')
            ->middleware(TestHeaderMiddleware::class)
            ->meta('auth', 'session');

        $this->app->make(CollectionArtifactStore::class)->compileAndWrite($router);
        $this->app->make(TreeArtifactStore::class)->compileAndWrite($router);
        $this->app->make(MetadataArtifactStore::class)->compileAndWrite($router);
        $this->app->make(PipelineArtifactStore::class)->compileAndWrite($router);
        $this->app->make(VersionArtifactStore::class)->compileAndWrite($router);

        $compiledCollection = $router->compiledCollection();
        $compiledRoute = $compiledCollection->named('artifact.production');

        self::assertNotNull($compiledRoute);
        self::assertNotSame($route, $compiledRoute);
        self::assertSame($compiledCollection, $router->compiledCollection());
        self::assertSame('session', $compiledRoute->routeMetadata()->get('auth'));

        $resolvedPipeline = $router->resolvedRoutePipeline($compiledRoute);

        self::assertNotSame($compiledRoute->routePipeline(), $resolvedPipeline);
        self::assertSame($compiledRoute->routePipeline()->id(), $resolvedPipeline->id());

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/artifact-production'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('ok', $response->content());
        self::assertSame('passed', $response->headers()['X-Middleware']);
    }

    public function test_it_generates_relative_named_route_urls_with_query_strings_and_fragments(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/users/{user}/posts/{post}', fn() => 'ok')->name('users.posts.show');

        $url = route('users.posts.show', [
            'user' => 42,
            'post' => 100,
            'filter' => 'recent',
            '_query' => ['page' => 2],
            '_fragment' => 'comments',
        ]);

        self::assertSame('/users/42/posts/100?filter=recent&page=2#comments', $url);
    }

    public function test_it_generates_domain_aware_relative_and_absolute_named_route_urls(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);
        $config->set('app.url', 'https://framework.test');

        $router = $this->app->make(Router::class);
        $router->get('/dashboard/{page}', fn() => 'ok')
            ->name('tenant.dashboard')
            ->domain('{tenant}.example.com');

        self::assertSame('//acme.example.com/dashboard/home', route('tenant.dashboard', [
            'tenant' => 'acme',
            'page' => 'home',
        ]));

        self::assertSame('https://acme.example.com/dashboard/home', route('tenant.dashboard', [
            'tenant' => 'acme',
            'page' => 'home',
        ], true));
    }

    public function test_it_generates_named_route_urls_from_loaded_collection_artifacts(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/artifact-url/{id}', TestStringController::class . '@show')->name('artifact.url');

        $this->app->make(CollectionArtifactStore::class)->compileAndWrite($router);
        $router->reloadCollectionArtifacts();

        self::assertSame('/artifact-url/42', $router->route('artifact.url', ['id' => 42]));
    }

    public function test_it_marks_internal_volt_routes_with_explicit_transport_metadata(): void
    {
        $router = $this->app->make(Router::class);
        $compiledRoutes = $router->compiledCollection()->all();
        $runtimeAssetRoute = null;
        $protocolActionRoute = null;

        foreach ($compiledRoutes as $route) {
            if ($route->uri() === '/_volt/runtime.js') {
                $runtimeAssetRoute = $route;
            }

            if ($route->uri() === '/_volt/action') {
                $protocolActionRoute = $route;
            }
        }

        self::assertNotNull($runtimeAssetRoute);
        self::assertNotNull($protocolActionRoute);
        self::assertSame('internal', $runtimeAssetRoute->routeMetadata()->get('transport'));
        self::assertSame('volt.runtime.asset', $runtimeAssetRoute->routeMetadata()->get('endpoint'));
        self::assertSame('volt', $runtimeAssetRoute->routeMetadata()->get('protocol'));
        self::assertSame('internal', $protocolActionRoute->routeMetadata()->get('transport'));
        self::assertSame('volt.protocol.action', $protocolActionRoute->routeMetadata()->get('endpoint'));
        self::assertSame('volt', $protocolActionRoute->routeMetadata()->get('protocol'));
    }

    public function test_it_exposes_the_current_runtime_http_verb_contract_through_internal_routes(): void
    {
        $router = $this->app->make(Router::class);
        $compiledRoutes = $router->compiledCollection()->all();
        $runtimeAssetRoute = null;
        $protocolActionRoute = null;

        foreach ($compiledRoutes as $route) {
            if ($route->uri() === '/_volt/runtime.js') {
                $runtimeAssetRoute = $route;
            }

            if ($route->uri() === '/_volt/action') {
                $protocolActionRoute = $route;
            }
        }

        self::assertNotNull($runtimeAssetRoute);
        self::assertNotNull($protocolActionRoute);
        self::assertSame(['GET'], $runtimeAssetRoute->methods());
        self::assertSame(['GET'], $runtimeAssetRoute->routeMetadata()->get('methods'));
        self::assertSame(['POST'], $protocolActionRoute->methods());
        self::assertSame(['POST'], $protocolActionRoute->routeMetadata()->get('methods'));
    }

    public function test_it_throws_a_clear_error_when_a_required_route_parameter_is_missing_during_url_generation(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/users/{id}', fn() => 'ok')->name('users.show');

        $this->expectException(RouteUrlGenerationException::class);
        $this->expectExceptionMessage('Missing required route parameter [id] for route [users.show].');

        route('users.show');
    }

    public function test_it_rejects_unknown_route_middleware_aliases_during_registration(): void
    {
        $router = $this->app->make(Router::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Middleware [missing] is not a registered alias or valid class.');

        $router->get('/missing-alias', fn() => 'ok')->middleware('missing');
    }

    public function test_it_deduplicates_global_middlewares_while_preserving_first_occurrence(): void
    {
        TestExecutionTrace::reset();

        $kernel = $this->app->make(HttpKernel::class);
        $kernel->aliasMiddleware('global-trace', TestGlobalTraceMiddleware::class);

        $router = $this->app->make(Router::class);
        $router->get('/global-dedupe', function (): string {
            TestExecutionTrace::push('action');

            return 'ok';
        });

        $kernel->setMiddlewares([
            'global-trace',
            TestGlobalTraceMiddleware::class,
        ]);
        $kernel->handle(Request::create('/global-dedupe'));

        self::assertSame([
            'global-before',
            'action',
            'global-after',
        ], TestExecutionTrace::all());
    }

    public function test_it_deduplicates_group_and_route_middlewares_after_alias_resolution(): void
    {
        TestExecutionTrace::reset();

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('route-trace', TestRouteTraceMiddleware::class);
        $router->group([
            'middleware' => ['route-trace', TestRouteTraceMiddleware::class],
        ], function (Router $router): void {
            $router->get('/route-dedupe', function (): string {
                TestExecutionTrace::push('action');

                return 'ok';
            })->middleware('route-trace');
        });

        $this->app->make(HttpKernel::class)->handle(Request::create('/route-dedupe'));

        self::assertSame([
            'route-before',
            'action',
            'route-after',
        ], TestExecutionTrace::all());
    }

    public function test_it_merges_group_and_route_metadata_and_exposes_it_on_the_request(): void
    {
        $router = $this->app->make(Router::class);
        $router->group([
            'metadata' => [
                'auth' => 'session',
                'runtime' => 'spa',
                'csrf' => true,
            ],
        ], function (Router $router): void {
            $router->get('/metadata', function (Request $request): array {
                return [
                    'all' => $request->routeMetadata(),
                    'auth' => $request->routeMeta('auth'),
                    'runtime' => $request->routeMeta('runtime'),
                    'csrf' => $request->routeMeta('csrf'),
                    'pipelineAuth' => $request->attribute('_pipeline_auth'),
                ];
            })
                ->name('meta.route')
                ->meta('runtime', 'ssr')
                ->guest()
                ->throttle('api')
                ->middleware(TestMetadataHeaderMiddleware::class);
        });

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/metadata'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('application/json; charset=UTF-8', $response->headers()['Content-Type']);
        self::assertSame('{"all":{"name":"meta.route","methods":["GET"],"domain":null,"middleware":["VoltStack\\\\Test\\\\Feature\\\\TestMetadataHeaderMiddleware"],"auth":"session","runtime":"ssr","csrf":true,"guest":true,"throttle":"api"},"auth":"session","runtime":"ssr","csrf":true,"pipelineAuth":"session"}', $response->content());
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

    public function test_it_dispatches_method_override_from_form_input(): void
    {
        $router = $this->app->make(Router::class);
        $router->patch('/users/42', fn() => 'patched');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create(
            '/users/42',
            'POST',
            [],
            ['_method' => 'patch'],
        ));

        self::assertSame(200, $response->statusCode());
        self::assertSame('patched', $response->content());
    }

    public function test_it_dispatches_method_override_from_header(): void
    {
        $router = $this->app->make(Router::class);
        $router->delete('/users/42', fn() => 'deleted');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create(
            '/users/42',
            'POST',
            [],
            [],
            [],
            [],
            [],
            ['HTTP_X_HTTP_METHOD_OVERRIDE' => 'delete'],
        ));

        self::assertSame(200, $response->statusCode());
        self::assertSame('deleted', $response->content());
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

    public function test_it_can_match_routes_bound_to_a_static_domain(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/dashboard', fn() => 'admin')->domain('admin.example.com');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/dashboard', 'GET', [], [], [], [], [], [
            'HTTP_HOST' => 'admin.example.com',
        ]));

        self::assertSame(200, $response->statusCode());
        self::assertSame('admin', $response->content());
    }

    public function test_it_can_resolve_domain_parameters_into_route_arguments(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/dashboard', fn(string $tenant) => 'tenant:' . $tenant)
            ->domain('{tenant}.example.com')
            ->whereAlphaNumeric('tenant');

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/dashboard', 'GET', [], [], [], [], [], [
            'HTTP_HOST' => 'acme42.example.com',
        ]));

        self::assertSame(200, $response->statusCode());
        self::assertSame('tenant:acme42', $response->content());
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

    public function test_it_reuses_runtime_route_metadata_when_bootstrapping_an_html_document(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/document-runtime-metadata', fn() => '<!DOCTYPE html><html><body><main>Document</main></body></html>')
            ->meta([
                'runtime' => [
                    'document' => 'reload',
                    'layout' => 'app-shell',
                    'navigation' => 'reload',
                    'hydrate' => [
                        'enabled' => true,
                        'strategy' => 'partial',
                        'dirtyState' => 'defer',
                    ],
                    'transition' => [
                        'name' => 'fade',
                        'profile' => 'smooth',
                        'duration' => 240,
                        'mode' => 'in-out',
                    ],
                ],
            ]);

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/document-runtime-metadata'));

        self::assertStringContainsString(
            '<body data-volt-document="reload" data-volt-navigation-mode="reload" data-volt-layout="app-shell" data-volt-page-transition="fade" data-volt-page-transition-profile="smooth" data-volt-page-transition-duration="240" data-volt-page-transition-mode="in-out" data-volt-hydrate="true" data-volt-hydrate-strategy="partial" data-volt-hydrate-dirty-state="defer">',
            $response->content(),
        );
        self::assertStringContainsString('data-volt-runtime="true"', $response->content());
    }

    public function test_it_reuses_rehydrated_metadata_artifacts_when_bootstrapping_an_html_document(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/artifact-document-runtime-metadata', TestHtmlDocumentController::class . '@show')
            ->name('artifact.document.runtime')
            ->meta([
                'runtime' => [
                    'document' => 'reload',
                    'layout' => 'app-shell',
                    'navigation' => 'reload',
                    'hydrate' => [
                        'enabled' => true,
                        'strategy' => 'partial',
                        'dirtyState' => 'defer',
                    ],
                    'transition' => [
                        'name' => 'fade',
                        'profile' => 'smooth',
                        'duration' => 240,
                        'mode' => 'in-out',
                    ],
                ],
            ]);

        $this->app->make(CollectionArtifactStore::class)->compileAndWrite($router);
        $this->app->make(MetadataArtifactStore::class)->compileAndWrite($router);
        $this->clearCollectionArtifactMetadata('artifact.document.runtime');

        $router->reloadCollectionArtifacts();

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/artifact-document-runtime-metadata'));

        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString(
            '<body data-volt-document="reload" data-volt-navigation-mode="reload" data-volt-layout="app-shell" data-volt-page-transition="fade" data-volt-page-transition-profile="smooth" data-volt-page-transition-duration="240" data-volt-page-transition-mode="in-out" data-volt-hydrate="true" data-volt-hydrate-strategy="partial" data-volt-hydrate-dirty-state="defer">',
            $response->content(),
        );
        self::assertStringContainsString('data-volt-runtime="true"', $response->content());
    }

    public function test_it_preserves_explicit_html_page_transition_options_over_runtime_metadata(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/document-runtime-transition-explicit', fn() => '<!DOCTYPE html><html><body data-volt-page-transition="slide" data-volt-page-transition-profile="cinematic" data-volt-page-transition-duration="360" data-volt-page-transition-mode="out-in"><main>Document</main></body></html>')
            ->meta([
                'runtime' => [
                    'transition' => [
                        'name' => 'fade',
                        'profile' => 'smooth',
                        'duration' => 240,
                        'mode' => 'in-out',
                    ],
                ],
            ]);

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/document-runtime-transition-explicit'));

        self::assertStringContainsString(
            '<body data-volt-page-transition="slide" data-volt-page-transition-profile="cinematic" data-volt-page-transition-duration="360" data-volt-page-transition-mode="out-in" data-volt-document="spa" data-volt-navigation-mode="auto">',
            $response->content(),
        );
    }

    public function test_it_projects_runtime_hydrate_metadata_without_overriding_explicit_html_hydration_markers(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/document-runtime-hydrate-explicit', fn() => '<!DOCTYPE html><html><body data-volt-hydrate="false" data-volt-hydrate-strategy="islands" data-volt-hydrate-dirty-state="eager"><main>Document</main></body></html>')
            ->meta([
                'runtime' => [
                    'hydrate' => [
                        'enabled' => true,
                        'strategy' => 'partial',
                        'dirtyState' => 'defer',
                    ],
                ],
            ]);

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/document-runtime-hydrate-explicit'));

        self::assertStringContainsString(
            '<body data-volt-hydrate="false" data-volt-hydrate-strategy="islands" data-volt-hydrate-dirty-state="eager" data-volt-document="spa" data-volt-navigation-mode="auto">',
            $response->content(),
        );
    }

    public function test_it_projects_runtime_layout_metadata_without_overriding_explicit_html_layout_markers(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/document-runtime-layout-explicit', fn() => '<!DOCTYPE html><html><body data-volt-layout="explicit-shell"><main>Document</main></body></html>')
            ->meta([
                'runtime' => [
                    'layout' => 'app-shell',
                ],
            ]);

        $response = $this->app->make(HttpKernel::class)->handle(Request::create('/document-runtime-layout-explicit'));

        self::assertStringContainsString(
            '<body data-volt-layout="explicit-shell" data-volt-document="spa" data-volt-navigation-mode="auto">',
            $response->content(),
        );
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
        self::assertStringContainsString('window.Volt.contract=', $response->content());
        self::assertStringContainsString('factory:"createPublicComponentsApi"', $response->content());
        self::assertStringContainsString('volt:component-destroyed', $response->content());
        self::assertStringContainsString('window.Volt.visit=function', $response->content());
        self::assertStringContainsString('factory:"createPublicTelemetryApi"', $response->content());
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

    private function clearCollectionArtifactMetadata(string $routeName): void
    {
        $path = $this->app->cachePath('routes/collection.php');
        /** @var mixed $payload */
        $payload = require $path;

        if (! is_array($payload) || ! isset($payload['routes']) || ! is_array($payload['routes'])) {
            self::fail('Collection artifact payload is invalid during metadata override test.');
        }

        foreach ($payload['routes'] as &$route) {
            if (($route['name'] ?? null) !== $routeName) {
                continue;
            }

            $route['metadata'] = [];
            break;
        }
        unset($route);

        file_put_contents($path, "<?php\n\nreturn " . var_export($payload, true) . ";\n");
    }

    /**
     * @return array<int, string>
     */
    private function routingArtifactPaths(): array
    {
        return [
            $this->app->cachePath('routes/collection.php'),
            $this->app->cachePath('routes/tree.php'),
            $this->app->cachePath('routes/metadata.php'),
            $this->app->cachePath('routes/pipeline.php'),
            $this->app->cachePath('routes/version.php'),
        ];
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

final class TestStringController
{
    public function show(): string
    {
        return 'controller-string';
    }
}

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class TestExplosiveRuntimeAttribute
{
    public function __construct()
    {
        throw new \RuntimeException('Runtime should not instantiate PHP attributes while dispatching routes.');
    }
}

#[TestExplosiveRuntimeAttribute]
final class TestAttributedController
{
    #[TestExplosiveRuntimeAttribute]
    public function show(#[TestExplosiveRuntimeAttribute] string $id): string
    {
        return 'attributes:' . $id;
    }
}

final class TestMetadataEchoController
{
    public function show(Request $request): array
    {
        return [
            'all' => $request->routeMetadata(),
            'auth' => $request->routeMeta('auth'),
            'runtime' => $request->routeMeta('runtime'),
            'csrf' => $request->routeMeta('csrf'),
            'guest' => $request->routeMeta('guest'),
        ];
    }
}

final class TestResponseController
{
    public function show(): Response
    {
        return new Response('ok');
    }
}

final class TestHtmlDocumentController
{
    public function show(): string
    {
        return '<!DOCTYPE html><html><body><main>Artifact document</main></body></html>';
    }
}

final class TestResolvedDependency
{
    public string $message = 'resolved';
}

final class TestShowUserAction extends Action
{
    public function handle(mixed ...$arguments): mixed
    {
        /** @var Request $request */
        $request = $arguments[0];
        $id = (string) ($arguments[1] ?? '');

        return 'action:' . $id . ':' . $request->path();
    }
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

final class TestExecutionTrace
{
    /**
     * @var array<int, string>
     */
    private static array $entries = [];

    public static function reset(): void
    {
        self::$entries = [];
    }

    public static function push(string $entry): void
    {
        self::$entries[] = $entry;
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return self::$entries;
    }
}

final class TestGlobalTraceMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): mixed
    {
        TestExecutionTrace::push('global-before');
        $response = $next($request);
        TestExecutionTrace::push('global-after');

        return $response;
    }
}

final class TestRouteTraceMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): mixed
    {
        TestExecutionTrace::push('route-before');
        $response = $next($request);
        TestExecutionTrace::push('route-after');

        return $response;
    }
}

final class TestOuterGroupTraceMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): mixed
    {
        TestExecutionTrace::push('group-outer-before');
        $response = $next($request);
        TestExecutionTrace::push('group-outer-after');

        return $response;
    }
}

final class TestInnerGroupTraceMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): mixed
    {
        TestExecutionTrace::push('group-inner-before');
        $response = $next($request);
        TestExecutionTrace::push('group-inner-after');

        return $response;
    }
}

final class TestMetadataHeaderMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): mixed
    {
        $request->setAttribute('_pipeline_auth', $request->routeMeta('auth', 'none'));
        $response = $next($request);

        if ($response instanceof Response) {
            $response->header('X-Route-Auth', (string) $request->routeMeta('auth', 'none'));
        }

        return $response;
    }
}