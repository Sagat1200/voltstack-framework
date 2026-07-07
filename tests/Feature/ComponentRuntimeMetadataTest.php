<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use Quantum\HttpKernel\HttpKernel;
use Quantum\Routing\Router;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\Component;

final class ComponentRuntimeMetadataTest extends TestCase
{
    public function test_it_uses_component_runtime_metadata_as_defaults_for_volt_navigation_payloads(): void
    {
        $app = new Application(sys_get_temp_dir());
        $router = $app->make(Router::class);
        $router->get('/runtime-defaults', TestComponentRuntimeDefaultsPage::class)
            ->name('runtime.defaults')
            ->componentPage();

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/runtime-defaults',
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
        self::assertSame('runtime.defaults', $payload['screen']['route'] ?? null);
        self::assertSame('reload', $payload['policy']['document'] ?? null);
        self::assertSame('reload', $payload['policy']['navigation'] ?? null);
        self::assertSame('component-shell', $payload['runtime']['layout'] ?? null);
        self::assertTrue($payload['runtime']['hydrate'] ?? false);
    }

    public function test_it_gives_route_runtime_metadata_precedence_over_component_defaults(): void
    {
        $app = new Application(sys_get_temp_dir());
        $router = $app->make(Router::class);
        $router->get('/runtime-overrides', TestComponentRuntimeDefaultsPage::class)
            ->name('runtime.overrides')
            ->meta([
                'runtime' => [
                    'layout' => 'route-shell',
                    'hydrate' => false,
                ],
            ])
            ->componentPage();

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/runtime-overrides',
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
        self::assertSame('runtime.overrides', $payload['screen']['route'] ?? null);
        self::assertSame('reload', $payload['policy']['document'] ?? null);
        self::assertSame('reload', $payload['policy']['navigation'] ?? null);
        self::assertSame('route-shell', $payload['runtime']['layout'] ?? null);
        self::assertFalse($payload['runtime']['hydrate'] ?? true);
    }
}

final class TestComponentRuntimeDefaultsPage extends Component
{
    public function runtimeMetadata(): array
    {
        return [
            'document' => 'reload',
            'navigation' => 'reload',
            'layout' => 'component-shell',
            'hydrate' => [
                'enabled' => true,
            ],
        ];
    }

    public function render(): string
    {
        return '<section>Runtime defaults</section>';
    }
}

