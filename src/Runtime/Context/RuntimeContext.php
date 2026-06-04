<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Context;

use Quantum\Http\Request;

final class RuntimeContext
{
    private static ?self $current = null;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $requestId,
        private readonly Request $request,
        private readonly float $startedAt,
        private array $metadata = [],
    ) {
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    public static function setCurrent(?self $context): void
    {
        self::$current = $context;
    }

    public function requestId(): string
    {
        return $this->requestId;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function startedAt(): float
    {
        return $this->startedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }
}
