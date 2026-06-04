<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Container\Container;

final class ScopedContainerTest extends TestCase
{
    public function test_scoped_bindings_are_cached_within_the_same_scope_and_reset_after_flush(): void
    {
        $container = new Container();
        $sequence = 0;

        $container->scoped('scoped.service', function () use (&$sequence): object {
            return (object) ['id' => ++$sequence];
        });

        $first = $container->make('scoped.service');
        $second = $container->make('scoped.service');

        self::assertSame($first, $second);
        self::assertSame(1, $first->id);

        $container->flushScope();

        $third = $container->make('scoped.service');

        self::assertNotSame($first, $third);
        self::assertSame(2, $third->id);
    }
}
