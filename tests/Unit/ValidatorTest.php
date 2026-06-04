<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Validation\Exceptions\ValidationException;
use Quantum\Validation\Validator;

final class ValidatorTest extends TestCase
{
    public function test_it_validates_and_returns_only_validated_fields(): void
    {
        $validator = new Validator();

        $validated = $validator->validate([
            'name' => 'VoltStack',
            'age' => '2',
            'active' => '1',
        ], [
            'name' => ['required', 'string', 'min:3'],
            'age' => ['required', 'integer'],
            'active' => ['required', 'boolean'],
        ]);

        self::assertSame('VoltStack', $validated['name']);
        self::assertSame('2', $validated['age']);
        self::assertSame('1', $validated['active']);
    }

    public function test_it_throws_a_validation_exception_when_rules_fail(): void
    {
        $this->expectException(ValidationException::class);

        $validator = new Validator();

        try {
            $validator->validate([
                'name' => '',
                'age' => 'abc',
            ], [
                'name' => ['required', 'string', 'min:3'],
                'age' => ['required', 'integer'],
            ]);
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('name', $exception->errors());
            self::assertArrayHasKey('age', $exception->errors());
            throw $exception;
        }
    }
}
