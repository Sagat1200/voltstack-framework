<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use Quantum\HttpKernel\HttpKernel;
use Quantum\Routing\Router;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\Component;

final class ComponentRouteRenderingTest extends TestCase
{
    public function test_a_route_can_mount_and_render_a_component_class(): void
    {
        $app = new Application(sys_get_temp_dir());
        $router = $app->make(Router::class);
        $router->get('/counter/{count}', TestCounterPage::class)
            ->name('counter.show')
            ->componentPage();

        $response = $app->make(HttpKernel::class)->handle(Request::create('/counter/7'));
        $snapshot = $this->extractSnapshot($response->content());

        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('data-volt-root="true"', $response->content());
        self::assertStringContainsString('data-volt-component="' . TestCounterPage::class . '"', $response->content());
        self::assertStringContainsString('data-volt-csrf="', $response->content());
        self::assertStringContainsString('<section>Count: 7</section>', $response->content());
        self::assertStringContainsString('data-volt-snapshot=', $response->content());
        self::assertSame('counter.show', $snapshot['meta']['route']['name'] ?? null);
        self::assertSame(['count' => '7'], $snapshot['meta']['route']['params'] ?? null);
        self::assertSame('component', $snapshot['meta']['route']['screen']['kind'] ?? null);
        self::assertSame('navigable', $snapshot['meta']['route']['screen']['mode'] ?? null);
        self::assertSame(1, substr_count($response->content(), 'data-volt-runtime="true"'));
        self::assertMatchesRegularExpression('/<script data-volt-runtime="true" src="\/_volt\/runtime\.js\?v=\d+" defer><\/script>/', $response->content());
    }

    public function test_it_emits_a_minimal_spa_navigation_payload_for_component_page_navigation_requests(): void
    {
        $app = new Application(sys_get_temp_dir());
        $router = $app->make(Router::class);
        $router->get('/counter/{count}', TestCounterPage::class)
            ->name('counter.show')
            ->componentPage();

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/counter/7',
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

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) ($response->headers()['X-Volt-Navigation'] ?? ''), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->statusCode());
        self::assertSame('/counter/7', $payload['navigation']['target'] ?? null);
        self::assertSame('GET', $payload['navigation']['method'] ?? null);
        self::assertSame('counter.show', $payload['screen']['route'] ?? null);
        self::assertNull($payload['error'] ?? null);
        self::assertStringContainsString('<section>Count: 7</section>', $response->content());
    }

    public function test_it_returns_a_reload_only_error_document_when_component_mount_fails(): void
    {
        $app = new Application(sys_get_temp_dir());
        $router = $app->make(Router::class);
        $router->get('/counter-mount-fail/{count}', TestMountFailurePage::class)
            ->name('counter.mount.fail')
            ->componentPage();

        $response = $app->make(HttpKernel::class)->handle(Request::create('/counter-mount-fail/7'));

        self::assertSame(500, $response->statusCode());
        self::assertSame('runtime.component_mount_failed', $response->headers()['X-Volt-Error-Code'] ?? null);
        self::assertStringContainsString('Server Error', $response->content());
        self::assertStringContainsString('<body data-volt-document="reload">', $response->content());
    }

    public function test_it_exposes_a_semantic_navigation_error_reason_when_component_render_fails(): void
    {
        $app = new Application(sys_get_temp_dir());
        $router = $app->make(Router::class);
        $router->get('/counter-render-fail', TestRenderFailurePage::class)
            ->name('counter.render.fail')
            ->componentPage();

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/counter-render-fail',
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

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) ($response->headers()['X-Volt-Navigation'] ?? ''), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(500, $response->statusCode());
        self::assertSame('runtime.component_render_failed', $response->headers()['X-Volt-Error-Code'] ?? null);
        self::assertSame([
            'code' => 500,
            'message' => 'Server Error',
            'reason' => 'runtime.component_render_failed',
        ], $payload['error'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractSnapshot(string $html): array
    {
        preg_match('/data-volt-snapshot="([^"]+)"/', $html, $matches);

        self::assertIsArray($matches);
        self::assertArrayHasKey(1, $matches);

        /** @var array<string, mixed> $snapshot */
        $snapshot = json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), true, 512, JSON_THROW_ON_ERROR);

        return $snapshot;
    }
}

final class TestCounterPage extends Component
{
    public int $count = 0;

    public function mount(string $count): void
    {
        $this->count = (int) $count;
    }

    public function render(): string
    {
        return sprintf('<section>Count: %d</section>', $this->count);
    }
}

final class TestMountFailurePage extends Component
{
    public function mount(): void
    {
        throw new \RuntimeException('Mount exploded.');
    }

    public function render(): string
    {
        return '<section>Never rendered</section>';
    }
}

final class TestRenderFailurePage extends Component
{
    public function render(): string
    {
        throw new \RuntimeException('Render exploded.');
    }
}
