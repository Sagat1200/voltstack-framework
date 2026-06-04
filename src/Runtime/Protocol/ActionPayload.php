<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

use RuntimeException;
use VoltStack\Runtime\Hydration\Snapshot;

final class ActionPayload
{
    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $updates
     */
    public function __construct(
        private readonly string $component,
        private readonly string $action,
        private readonly Snapshot $snapshot,
        private readonly array $params = [],
        private readonly array $updates = [],
    ) {}

    public function component(): string
    {
        return $this->component;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function snapshot(): Snapshot
    {
        return $this->snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * @return array<string, mixed>
     */
    public function updates(): array
    {
        return $this->updates;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        if (! isset($payload['component'], $payload['action'], $payload['snapshot']) || ! is_array($payload['snapshot'])) {
            throw new RuntimeException('Invalid reactive action payload.');
        }

        return new self(
            (string) $payload['component'],
            (string) $payload['action'],
            Snapshot::fromArray($payload['snapshot']),
            is_array($payload['params'] ?? null) ? $payload['params'] : [],
            is_array($payload['updates'] ?? null) ? $payload['updates'] : [],
        );
    }
}