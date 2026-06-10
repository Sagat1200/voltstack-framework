<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Http\RedirectResponse;
use Quantum\Http\Request;
use Quantum\Security\CsrfTokenManager;
use Quantum\HttpKernel\HttpKernel;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\Component;
use VoltStack\Runtime\Component\ComponentManager;
use VoltStack\Runtime\Protocol\ActionEffectOptions;

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
        self::assertSame('html.replace', $payload['effects'][0]['type']);
        self::assertSame('root', $payload['effects'][0]['target']);
        self::assertStringContainsString('Count: 3', $payload['effects'][0]['html']);
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
        self::assertSame('html.replace', $payload['effects'][0]['type']);
        self::assertStringContainsString('value="VoltStack Title"', $payload['html']);
        self::assertStringContainsString('Saved: saved-from-submit', $payload['html']);
    }

    public function test_it_returns_a_navigation_effect_when_the_action_redirects(): void
    {
        $app = new Application(sys_get_temp_dir());
        $components = $app->make(ComponentManager::class);
        $component = $components->mount(TestReactiveRedirectComponent::class);
        $snapshot = $components->dehydrate($component);

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                '_token' => $app->make(CsrfTokenManager::class)->token(),
                'component' => TestReactiveRedirectComponent::class,
                'action' => 'goHome',
                'params' => [],
                'snapshot' => $snapshot->toArray(),
            ],
        ));

        self::assertSame(200, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('navigate', $payload['effects'][0]['type']);
        self::assertSame('/', $payload['effects'][0]['url']);
        self::assertFalse($payload['effects'][0]['replace']);
    }

    public function test_it_returns_granular_effects_for_targeted_html_changes(): void
    {
        $app = new Application(sys_get_temp_dir());
        $components = $app->make(ComponentManager::class);
        $component = $components->mount(TestReactiveTargetedComponent::class);
        $snapshot = $components->dehydrate($component);

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                '_token' => $app->make(CsrfTokenManager::class)->token(),
                'component' => TestReactiveTargetedComponent::class,
                'action' => 'increment',
                'params' => [],
                'snapshot' => $snapshot->toArray(),
            ],
        ));

        self::assertSame(200, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(2, $payload['effects']);
        self::assertSame('attribute.set', $payload['effects'][0]['type']);
        self::assertSame('action-button', $payload['effects'][0]['target']);
        self::assertSame('disabled', $payload['effects'][0]['name']);
        self::assertSame('disabled', $payload['effects'][0]['value']);
        self::assertSame('text.update', $payload['effects'][1]['type']);
        self::assertSame('counter-value', $payload['effects'][1]['target']);
        self::assertSame('1', $payload['effects'][1]['value']);
    }

    public function test_it_returns_class_and_style_effects_for_semantic_target_changes(): void
    {
        $app = new Application(sys_get_temp_dir());
        $components = $app->make(ComponentManager::class);
        $component = $components->mount(TestReactiveStyledTargetedComponent::class);
        $snapshot = $components->dehydrate($component);

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                '_token' => $app->make(CsrfTokenManager::class)->token(),
                'component' => TestReactiveStyledTargetedComponent::class,
                'action' => 'activate',
                'params' => [],
                'snapshot' => $snapshot->toArray(),
            ],
        ));

        self::assertSame(200, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(4, $payload['effects']);
        self::assertSame('class.toggle', $payload['effects'][0]['type']);
        self::assertSame('status-badge', $payload['effects'][0]['target']);
        self::assertSame('active', $payload['effects'][0]['class']);
        self::assertTrue($payload['effects'][0]['force']);
        self::assertSame('style.set', $payload['effects'][1]['type']);
        self::assertSame('status-badge', $payload['effects'][1]['target']);
        self::assertSame('color', $payload['effects'][1]['property']);
        self::assertSame('red', $payload['effects'][1]['value']);
        self::assertSame('style.set', $payload['effects'][2]['type']);
        self::assertSame('status-badge', $payload['effects'][2]['target']);
        self::assertSame('font-weight', $payload['effects'][2]['property']);
        self::assertSame('700', $payload['effects'][2]['value']);
        self::assertSame('text.update', $payload['effects'][3]['type']);
        self::assertSame('status-text', $payload['effects'][3]['target']);
        self::assertSame('Active', $payload['effects'][3]['value']);
    }

    public function test_it_allows_the_backend_to_attach_transition_options_to_generated_effects(): void
    {
        $app = new Application(sys_get_temp_dir());
        $components = $app->make(ComponentManager::class);
        $component = $components->mount(TestReactiveTransitionedComponent::class);
        $snapshot = $components->dehydrate($component);

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                '_token' => $app->make(CsrfTokenManager::class)->token(),
                'component' => TestReactiveTransitionedComponent::class,
                'action' => 'increment',
                'params' => [],
                'snapshot' => $snapshot->toArray(),
            ],
        ));

        self::assertSame(200, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $payload['effects']);
        self::assertSame('text.update', $payload['effects'][0]['type']);
        self::assertSame('counter-value', $payload['effects'][0]['target']);
        self::assertSame('pop', $payload['effects'][0]['transition']['name']);
        self::assertSame(220, $payload['effects'][0]['transition']['duration']);
        self::assertSame('glow', $payload['effects'][0]['transitions']['update']['name']);
        self::assertSame('volt-transition-soft-edge', $payload['effects'][0]['transitions']['update']['className']);
    }

    public function test_it_returns_dom_append_for_stable_target_lists(): void
    {
        $app = new Application(sys_get_temp_dir());
        $components = $app->make(ComponentManager::class);
        $component = $components->mount(TestReactiveListComponent::class);
        $snapshot = $components->dehydrate($component);

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                '_token' => $app->make(CsrfTokenManager::class)->token(),
                'component' => TestReactiveListComponent::class,
                'action' => 'append',
                'params' => [],
                'snapshot' => $snapshot->toArray(),
            ],
        ));

        self::assertSame(200, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $payload['effects']);
        self::assertSame('dom.append', $payload['effects'][0]['type']);
        self::assertSame('todo-list', $payload['effects'][0]['target']);
        self::assertSame('beforeend', $payload['effects'][0]['position']);
        self::assertStringContainsString('data-volt-key="item-3"', $payload['effects'][0]['html']);
        self::assertStringContainsString('Third', $payload['effects'][0]['html']);
    }

    public function test_it_returns_dom_remove_for_stable_target_lists(): void
    {
        $app = new Application(sys_get_temp_dir());
        $components = $app->make(ComponentManager::class);
        $component = $components->mount(TestReactiveListComponent::class, [
            'items' => [
                ['key' => 'item-1', 'label' => 'First'],
                ['key' => 'item-2', 'label' => 'Second'],
                ['key' => 'item-3', 'label' => 'Third'],
            ],
        ]);
        $snapshot = $components->dehydrate($component);

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                '_token' => $app->make(CsrfTokenManager::class)->token(),
                'component' => TestReactiveListComponent::class,
                'action' => 'removeLast',
                'params' => [],
                'snapshot' => $snapshot->toArray(),
            ],
        ));

        self::assertSame(200, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $payload['effects']);
        self::assertSame('dom.remove', $payload['effects'][0]['type']);
        self::assertSame('[data-volt-target="todo-list"] > [data-volt-key="item-3"]', $payload['effects'][0]['selector']);
    }

    public function test_it_returns_html_replace_for_a_single_changed_item_in_a_keyed_list(): void
    {
        $app = new Application(sys_get_temp_dir());
        $components = $app->make(ComponentManager::class);
        $component = $components->mount(TestReactiveListComponent::class);
        $snapshot = $components->dehydrate($component);

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                '_token' => $app->make(CsrfTokenManager::class)->token(),
                'component' => TestReactiveListComponent::class,
                'action' => 'updateSecond',
                'params' => [],
                'snapshot' => $snapshot->toArray(),
            ],
        ));

        self::assertSame(200, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $payload['effects']);
        self::assertSame('html.replace', $payload['effects'][0]['type']);
        self::assertSame('[data-volt-target="todo-list"] > [data-volt-key="item-2"]', $payload['effects'][0]['selector']);
        self::assertStringContainsString('data-volt-key="item-2"', $payload['effects'][0]['html']);
        self::assertStringContainsString('Second updated', $payload['effects'][0]['html']);
        self::assertTrue($payload['effects'][0]['outer']);
    }

    public function test_it_returns_dom_insert_for_a_middle_insertion_in_a_keyed_list(): void
    {
        $app = new Application(sys_get_temp_dir());
        $components = $app->make(ComponentManager::class);
        $component = $components->mount(TestReactiveListComponent::class);
        $snapshot = $components->dehydrate($component);

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                '_token' => $app->make(CsrfTokenManager::class)->token(),
                'component' => TestReactiveListComponent::class,
                'action' => 'insertMiddle',
                'params' => [],
                'snapshot' => $snapshot->toArray(),
            ],
        ));

        self::assertSame(200, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $payload['effects']);
        self::assertSame('dom.insert', $payload['effects'][0]['type']);
        self::assertSame('todo-list', $payload['effects'][0]['target']);
        self::assertSame('[data-volt-target="todo-list"] > [data-volt-key="item-2"]', $payload['effects'][0]['beforeSelector']);
        self::assertSame('beforebegin', $payload['effects'][0]['position']);
        self::assertStringContainsString('data-volt-key="item-1-5"', $payload['effects'][0]['html']);
        self::assertStringContainsString('Between', $payload['effects'][0]['html']);
    }

    public function test_it_returns_dom_move_for_a_reordered_keyed_list(): void
    {
        $app = new Application(sys_get_temp_dir());
        $components = $app->make(ComponentManager::class);
        $component = $components->mount(TestReactiveListComponent::class, [
            'items' => [
                ['key' => 'item-1', 'label' => 'First'],
                ['key' => 'item-2', 'label' => 'Second'],
                ['key' => 'item-3', 'label' => 'Third'],
            ],
        ]);
        $snapshot = $components->dehydrate($component);

        $response = $app->make(HttpKernel::class)->handle(Request::create(
            '/_volt/action',
            'POST',
            [],
            [
                '_token' => $app->make(CsrfTokenManager::class)->token(),
                'component' => TestReactiveListComponent::class,
                'action' => 'moveLastToFirst',
                'params' => [],
                'snapshot' => $snapshot->toArray(),
            ],
        ));

        self::assertSame(200, $response->statusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $payload['effects']);
        self::assertSame('dom.move', $payload['effects'][0]['type']);
        self::assertSame('[data-volt-target="todo-list"] > [data-volt-key="item-3"]', $payload['effects'][0]['selector']);
        self::assertSame('todo-list', $payload['effects'][0]['parentTarget']);
        self::assertSame('[data-volt-target="todo-list"] > [data-volt-key="item-1"]', $payload['effects'][0]['beforeSelector']);
        self::assertSame('beforebegin', $payload['effects'][0]['position']);
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

final class TestReactiveRedirectComponent extends Component
{
    public function goHome(): RedirectResponse
    {
        return response()->redirect('/');
    }

    public function render(): string
    {
        return '<button type="button" volt-click="goHome">Go home</button>';
    }
}

final class TestReactiveTargetedComponent extends Component
{
    public int $count = 0;

    public bool $locked = false;

    public function increment(): void
    {
        $this->count++;
        $this->locked = true;
    }

    public function render(): string
    {
        return sprintf(
            '<div><span data-volt-target="counter-value">%d</span><button data-volt-target="action-button" type="button"%s>Increment</button></div>',
            $this->count,
            $this->locked ? ' disabled' : '',
        );
    }
}

final class TestReactiveStyledTargetedComponent extends Component
{
    public bool $active = false;

    public function activate(): void
    {
        $this->active = true;
    }

    public function render(): string
    {
        return sprintf(
            '<div><span data-volt-target="status-badge" class="badge%s"%s>Badge</span><span data-volt-target="status-text">%s</span></div>',
            $this->active ? ' active' : '',
            $this->active ? ' style="color:red;font-weight:700"' : '',
            $this->active ? 'Active' : 'Idle',
        );
    }
}

final class TestReactiveTransitionedComponent extends Component
{
    public int $count = 0;

    public function increment(): ActionEffectOptions
    {
        $this->count++;

        return ActionEffectOptions::make()
            ->transition([
                'name' => 'pop',
                'duration' => 220,
            ], type: 'text.update', target: 'counter-value')
            ->transitions([
                'update' => [
                    'name' => 'glow',
                    'className' => 'volt-transition-soft-edge',
                ],
            ], type: 'text.update', target: 'counter-value');
    }

    public function render(): string
    {
        return sprintf(
            '<div><span data-volt-target="counter-value">%d</span></div>',
            $this->count,
        );
    }
}

final class TestReactiveListComponent extends Component
{
    /**
     * @var array<int, array{key: string, label: string}>
     */
    public array $items = [
        ['key' => 'item-1', 'label' => 'First'],
        ['key' => 'item-2', 'label' => 'Second'],
    ];

    /**
     * @param array<int, array{key: string, label: string}> $items
     */
    public function mount(array $items = []): void
    {
        if ($items !== []) {
            $this->items = $items;
        }
    }

    public function append(): void
    {
        $this->items[] = [
            'key' => 'item-' . (count($this->items) + 1),
            'label' => 'Third',
        ];
    }

    public function removeLast(): void
    {
        array_pop($this->items);
    }

    public function updateSecond(): void
    {
        $this->items[1]['label'] = 'Second updated';
    }

    public function insertMiddle(): void
    {
        array_splice($this->items, 1, 0, [[
            'key' => 'item-1-5',
            'label' => 'Between',
        ]]);
    }

    public function moveLastToFirst(): void
    {
        $last = array_pop($this->items);

        if ($last === null) {
            return;
        }

        array_unshift($this->items, $last);
    }

    public function render(): string
    {
        $items = array_map(
            static fn(array $item): string => sprintf(
                '<li data-volt-key="%s">%s</li>',
                e($item['key']),
                e($item['label']),
            ),
            $this->items,
        );

        return '<ul data-volt-target="todo-list">' . implode('', $items) . '</ul>';
    }
}
