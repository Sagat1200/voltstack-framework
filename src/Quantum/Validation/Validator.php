<?php

declare(strict_types=1);

namespace Quantum\Validation;

use Quantum\Validation\Exceptions\ValidationException;

final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, array<int, string>|string> $rules
     * @return array<string, mixed>
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $parsedRules = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);

            foreach ($parsedRules as $rule) {
                [$name, $parameter] = $this->parseRule($rule);

                if ($this->passes($name, $value, $parameter)) {
                    continue;
                }

                $errors[$field][] = $this->message($field, $name, $parameter);
            }

            if (! isset($errors[$field]) && array_key_exists($field, $data)) {
                $validated[$field] = $value;
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    private function parseRule(string $rule): array
    {
        if (! str_contains($rule, ':')) {
            return [$rule, null];
        }

        [$name, $parameter] = explode(':', $rule, 2);

        return [$name, $parameter];
    }

    private function passes(string $rule, mixed $value, ?string $parameter): bool
    {
        return match ($rule) {
            'required' => ! ($value === null || $value === ''),
            'string' => $value === null || is_string($value),
            'integer', 'int' => $value === null || filter_var($value, FILTER_VALIDATE_INT) !== false,
            'boolean', 'bool' => $value === null || is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true),
            'array' => $value === null || is_array($value),
            'min' => $this->validateMin($value, $parameter),
            default => true,
        };
    }

    private function validateMin(mixed $value, ?string $parameter): bool
    {
        if ($value === null || $parameter === null) {
            return true;
        }

        $min = (int) $parameter;

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_numeric($value)) {
            return (float) $value >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    private function message(string $field, string $rule, ?string $parameter): string
    {
        return match ($rule) {
            'required' => sprintf('The %s field is required.', $field),
            'string' => sprintf('The %s field must be a string.', $field),
            'integer', 'int' => sprintf('The %s field must be an integer.', $field),
            'boolean', 'bool' => sprintf('The %s field must be a boolean.', $field),
            'array' => sprintf('The %s field must be an array.', $field),
            'min' => sprintf('The %s field must be at least %s.', $field, $parameter ?? '0'),
            default => sprintf('The %s field is invalid.', $field),
        };
    }
}
