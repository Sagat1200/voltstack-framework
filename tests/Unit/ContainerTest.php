<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Container\Container;

final class ContainerTest extends TestCase
{
    public function test_it_resolves_class_dependencies_automatically(): void
    {
        $container = new Container();

        $consumer = $container->make(ContainerTestConsumer::class);

        self::assertInstanceOf(ContainerTestConsumer::class, $consumer);
        self::assertInstanceOf(ContainerTestDependency::class, $consumer->dependency);
    }

    public function test_it_returns_the_same_singleton_instance(): void
    {
        $container = new Container();
        $container->singleton(ContainerTestDependency::class);

        $first = $container->make(ContainerTestDependency::class);
        $second = $container->make(ContainerTestDependency::class);

        self::assertSame($first, $second);
    }

    public function test_it_supports_named_parameter_overrides(): void
    {
        $container = new Container();

        $consumer = $container->make(ContainerTestScalarConsumer::class, [
            'name' => 'VoltStack',
        ]);

        self::assertSame('VoltStack', $consumer->name);
    }
}

final class ContainerTestDependency
{
}

final class ContainerTestConsumer
{
    public function __construct(public readonly ContainerTestDependency $dependency)
    {
    }
}

final class ContainerTestScalarConsumer
{
    public function __construct(public readonly string $name)
    {
    }
}
