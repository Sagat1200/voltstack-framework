<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Component;

final class ComponentAttributeBag
{
    /**
     * @var array<string, mixed>
     */
    private array $attributes;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $this->normalize($attributes);
    }

    /**
     * @param array<string, mixed>|self $attributes
     */
    public function merge(array|self $attributes): self
    {
        $defaults = $attributes instanceof self ? $attributes->all() : $attributes;
        $defaults = $this->normalize($defaults);
        $merged = $defaults;

        foreach ($this->attributes as $key => $value) {
            if ($key === 'class' && isset($merged[$key])) {
                $merged[$key] = $this->mergeClassValues($merged[$key], $value);
                continue;
            }

            $merged[$key] = $value;
        }

        return new self($merged);
    }

    public static function formatClasses(mixed $value): string
    {
        return self::normalizeClassValueStatic($value);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->attributes;
    }

    public function __toString(): string
    {
        $parts = [];

        foreach ($this->attributes as $name => $value) {
            if ($value === null || $value === false || $value === '') {
                continue;
            }

            if ($value === true) {
                $parts[] = $name;
                continue;
            }

            $parts[] = sprintf('%s="%s"', $name, e((string) $value));
        }

        return implode(' ', $parts);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalize(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $key => $value) {
            if (is_int($key)) {
                if (is_string($value) && trim($value) !== '') {
                    $normalized[$value] = true;
                }

                continue;
            }

            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            $normalized[$key] = $key === 'class'
                ? self::normalizeClassValueStatic($value)
                : $this->normalizeAttributeValue($value);
        }

        return $normalized;
    }

    private function normalizeAttributeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $parts = [];

            foreach ($value as $item) {
                if (is_scalar($item) && (string) $item !== '') {
                    $parts[] = (string) $item;
                }
            }

            return implode(' ', $parts);
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return (string) json_encode($value, JSON_THROW_ON_ERROR);
    }

    private static function normalizeClassValueStatic(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (! is_array($value)) {
            return is_scalar($value) ? trim((string) $value) : '';
        }

        $classes = [];

        foreach ($value as $key => $entry) {
            if (is_int($key)) {
                if (is_scalar($entry) && trim((string) $entry) !== '') {
                    $classes[] = trim((string) $entry);
                }

                continue;
            }

            if ($entry) {
                $classes[] = trim((string) $key);
            }
        }

        return implode(' ', array_values(array_filter($classes, static fn(string $class): bool => $class !== '')));
    }

    private function mergeClassValues(mixed $defaults, mixed $current): string
    {
        $values = array_filter([
            self::normalizeClassValueStatic($defaults),
            self::normalizeClassValueStatic($current),
        ], static fn(string $class): bool => $class !== '');

        return implode(' ', $values);
    }
}
