<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;

final class RequestTest extends TestCase
{
    public function test_it_uses_the_original_method_when_no_override_is_present(): void
    {
        $request = Request::create('/users', 'POST');

        self::assertSame('POST', $request->originalMethod());
        self::assertSame('POST', $request->method());
    }

    public function test_it_applies_form_method_override_for_post_requests(): void
    {
        $request = Request::create('/users/42', 'POST', [], ['_method' => 'patch']);

        self::assertSame('POST', $request->originalMethod());
        self::assertSame('PATCH', $request->method());
    }

    public function test_it_prioritizes_header_method_override_over_form_input(): void
    {
        $request = Request::create('/users/42', 'POST', [], ['_method' => 'patch'], [], [], [], [
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'delete',
        ]);

        self::assertSame('DELETE', $request->method());
    }

    public function test_it_ignores_method_override_for_non_post_requests(): void
    {
        $request = Request::create('/users/42', 'GET', [], ['_method' => 'delete'], [], [], [], [
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'patch',
        ]);

        self::assertSame('GET', $request->method());
    }

    public function test_it_ignores_unsupported_override_methods(): void
    {
        $request = Request::create('/users/42', 'POST', [], ['_method' => 'get'], [], [], [], [
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'options',
        ]);

        self::assertSame('POST', $request->method());
    }

    public function test_it_does_not_override_the_internal_volt_action_endpoint(): void
    {
        $request = Request::create('/_volt/action', 'POST', [], ['_method' => 'delete'], [], [], [], [
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'patch',
        ]);

        self::assertSame('POST', $request->method());
        self::assertTrue($request->isVoltActionRequest());
    }
}
