<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use Quantum\View\ViewFactory;
use Quantum\View\Runtime\ViewRuntime;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\ComponentAttributeBag;
use VoltStack\Runtime\Component\Component;
use VoltStack\Runtime\Component\ComponentManager;
use VoltStack\Runtime\Hydration\Exceptions\InvalidSnapshotException;
use VoltStack\Runtime\Hydration\Snapshot;

final class ComponentHydrationTest extends TestCase
{
    public function test_it_mounts_dehydrates_and_hydrates_a_component(): void
    {
        $app = new Application(sys_get_temp_dir());
        $manager = $app->make(ComponentManager::class);
        $request = Request::create('/hello/voltstack');

        $mounted = $manager->mount(TestGreetingComponent::class, [
            'name' => 'VoltStack',
        ], $request);

        self::assertSame('VoltStack', $mounted->name);
        self::assertSame(1, $mounted->visits);
        self::assertSame('/hello/voltstack', $mounted->request()?->path());

        $snapshot = $manager->dehydrate($mounted, ['phase' => '0.4.x']);
        $hydrated = $manager->hydrate(TestGreetingComponent::class, $snapshot, $request);

        self::assertSame('VoltStack', $hydrated->name);
        self::assertSame(1, $hydrated->visits);
        self::assertSame('<div>Hello VoltStack (1)</div>', $manager->render($hydrated));
        self::assertStringContainsString('data-volt-root="true"', $manager->renderRoot($hydrated, $snapshot));
    }

    public function test_it_rejects_a_snapshot_with_an_invalid_checksum(): void
    {
        $this->expectException(InvalidSnapshotException::class);

        $app = new Application(sys_get_temp_dir());
        $manager = $app->make(ComponentManager::class);

        $manager->hydrate(TestGreetingComponent::class, Snapshot::fromArray([
            'component' => TestGreetingComponent::class,
            'state' => [
                'name' => 'Tampered',
                'visits' => 99,
            ],
            'checksum' => 'invalid',
            'meta' => [],
        ]));
    }

    public function test_it_normalizes_component_props_definitions(): void
    {
        $app = new Application(sys_get_temp_dir());
        $runtime = new ViewRuntime($app->make(ViewFactory::class));

        self::assertSame(
            [
                'title' => null,
                'size' => 'md',
            ],
            $runtime->normalizeProps([
                'title',
                'size' => 'md',
            ]),
        );
    }

    public function test_it_merges_component_attribute_bags_preserving_explicit_attributes(): void
    {
        $attributes = new ComponentAttributeBag([
            'class' => 'shadow-lg',
            'id' => 'card-1',
        ]);

        $merged = $attributes->merge([
            'class' => 'card rounded',
            'data-kind' => 'panel',
        ]);

        self::assertSame(
            [
                'class' => 'card rounded shadow-lg',
                'data-kind' => 'panel',
                'id' => 'card-1',
            ],
            $merged->all(),
        );
        self::assertSame('class="card rounded shadow-lg" data-kind="panel" id="card-1"', (string) $merged);
    }

    public function test_it_formats_conditional_class_lists(): void
    {
        self::assertSame(
            'btn btn-primary',
            ComponentAttributeBag::formatClasses([
                'btn',
                'btn-primary' => true,
                'btn-secondary' => false,
            ]),
        );
    }
}

final class TestGreetingComponent extends Component
{
    public string $name = 'Guest';

    public int $visits = 0;

    public function mount(string $name = 'Guest'): void
    {
        $this->name = $name;
        $this->visits++;
    }

    public function render(): string
    {
        return sprintf('<div>Hello %s (%d)</div>', $this->name, $this->visits);
    }
}
