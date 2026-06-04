<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use VoltStack\Framework\Application;
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
