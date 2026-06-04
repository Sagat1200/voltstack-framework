<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use Quantum\Security\CsrfTokenManager;
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
        $csrf = $app->make(CsrfTokenManager::class)->token();

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                '_token' => $csrf,
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
                '_token' => $app->make(CsrfTokenManager::class)->token(),
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

    public function test_it_rejects_reactive_requests_without_a_valid_csrf_token(): void
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

        self::assertSame(422, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('CSRF token mismatch.', $payload['error']['message']);
    }

    public function test_it_applies_model_updates_and_form_params_before_running_the_action(): void
    {
        $app = new Application(sys_get_temp_dir());
        $components = $app->make(ComponentManager::class);
        $component = $components->mount(TestReactiveFormComponent::class);
        $snapshot = $components->dehydrate($component);

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                '_token' => $app->make(CsrfTokenManager::class)->token(),
                'component' => TestReactiveFormComponent::class,
                'action' => 'save',
                'params' => [
                    'note' => 'saved-from-submit',
                    '_token' => 'ignored-token-field',
                ],
                'updates' => [
                    'title' => 'VoltStack Title',
                ],
                'snapshot' => $snapshot->toArray(),
            ],
        ));

        self::assertSame(200, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('VoltStack Title', $payload['snapshot']['state']['title']);
        self::assertSame('saved-from-submit', $payload['snapshot']['state']['savedNote']);
        self::assertStringContainsString('value="VoltStack Title"', $payload['html']);
        self::assertStringContainsString('Saved: saved-from-submit', $payload['html']);
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

final class TestReactiveFormComponent extends Component
{
    public string $title = '';

    public string $savedNote = '';

    public function save(string $note = ''): void
    {
        $this->validate([
            'title' => $this->title,
        ], [
            'title' => ['required', 'string', 'min:3'],
        ]);

        $this->savedNote = $note;
    }

    public function render(): string
    {
        return sprintf(
            '<form volt-submit="save">%s<input type="text" volt-model="title" value="%s"><button type="submit">Save</button><span>Saved: %s</span></form>',
            csrf_field(),
            e($this->title),
            e($this->savedNote),
        );
    }
}
