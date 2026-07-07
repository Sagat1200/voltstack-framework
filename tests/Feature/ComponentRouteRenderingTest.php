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
