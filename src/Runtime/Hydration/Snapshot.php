<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Hydration;

final class Snapshot
{
    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $meta
     */
    public function __construct(
        private readonly string $component,
        private readonly array $state,
        private readonly string $checksum,
        private readonly array $meta = [],
    ) {
    }

    public function component(): string
    {
        return $this->component;
    }

    /**
     * @return array<string, mixed>
     */
    public function state(): array
    {
        return $this->state;
    }

    public function checksum(): string
    {
        return $this->checksum;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'component' => $this->component,
            'state' => $this->state,
            'checksum' => $this->checksum,
            'meta' => $this->meta,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['component'] ?? ''),
            is_array($payload['state'] ?? null) ? $payload['state'] : [],
            (string) ($payload['checksum'] ?? ''),
            is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
        );
    }
}
