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
        $router->get('/counter/{count}', TestCounterPage::class);

        $response = $app->make(HttpKernel::class)->handle(Request::create('/counter/7'));

        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('data-volt-root="true"', $response->content());
        self::assertStringContainsString('data-volt-component="' . TestCounterPage::class . '"', $response->content());
        self::assertStringContainsString('data-volt-csrf="', $response->content());
        self::assertStringContainsString('<section>Count: 7</section>', $response->content());
        self::assertStringContainsString('data-volt-snapshot=', $response->content());
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
