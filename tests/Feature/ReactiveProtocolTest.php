<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use Quantum\HttpKernel\HttpKernel;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\Component;
use VoltStack\Runtime\Component\ComponentManager;

final class ReactiveProtocolTest extends TestCase
{
    public function test_it_executes_a_component_action_through_the_protocol_endpoint(): void
    {
        $app = new Application(sys_get_temp_dir());
        $components = $app->make(ComponentManager::class);
        $component = $components->mount(TestReactiveCounter::class, [
            'count' => 2,
        ]);
        $snapshot = $components->dehydrate($component);

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                'component' => TestReactiveCounter::class,
                'action' => 'increment',
                'params' => [],
                'snapshot' => $snapshot->toArray(),
            ],
        ));

        self::assertSame(200, $response->statusCode());
        self::assertSame('application/json; charset=UTF-8', $response->headers()['Content-Type']);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(TestReactiveCounter::class, $payload['component']);
        self::assertSame(3, $payload['snapshot']['state']['count']);
        self::assertSame('increment', $payload['meta']['action']);
        self::assertStringContainsString('<button type="button" volt-click="increment">Count: 3</button>', $payload['html']);
        self::assertStringContainsString('data-volt-root="true"', $payload['html']);
    }

    public function test_it_returns_a_validation_error_when_the_snapshot_is_invalid(): void
    {
        $app = new Application(sys_get_temp_dir());

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                'component' => TestReactiveCounter::class,
                'action' => 'increment',
                'params' => [],
                'snapshot' => [
                    'component' => TestReactiveCounter::class,
                    'state' => ['count' => 999],
                    'checksum' => 'invalid',
                    'meta' => [],
                ],
            ],
        ));

        self::assertSame(422, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Snapshot checksum is invalid.', $payload['error']['message']);
    }
}

final class TestReactiveCounter extends Component
{
    public int $count = 0;

    public function mount(int $count = 0): void
    {
        $this->count = $count;
    }

    public function increment(): void
    {
        $this->count++;
    }

    public function render(): string
    {
        return sprintf(
            '<button type="button" volt-click="increment">Count: %d</button>',
            $this->count,
        );
    }
}
