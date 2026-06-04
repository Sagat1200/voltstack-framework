<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Actions\Action;
use VoltStack\Framework\Application;

final class ActionTest extends TestCase
{
    public function test_action_run_resolves_the_action_from_the_container(): void
    {
        new Application(sys_get_temp_dir());

        $result = TestUppercaseAction::run('voltstack');

        self::assertSame('VOLTSTACK', $result);
    }
}

final class TestUppercaseAction extends Action
{
    public function handle(mixed ...$arguments): mixed
    {
        return strtoupper((string) ($arguments[0] ?? ''));
    }
}
