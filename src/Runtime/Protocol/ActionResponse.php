<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

use VoltStack\Runtime\Hydration\Snapshot;

final class ActionResponse
{
    /**
     * @param array<int, mixed> $effects
     * @param array<string, mixed> $meta
     */
    public function __construct(
        private readonly string $component,
        private readonly string $html,
        private readonly Snapshot $snapshot,
        private readonly array $effects = [],
        private readonly array $meta = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'component' => $this->component,
            'html' => $this->html,
            'snapshot' => $this->snapshot->toArray(),
            'effects' => $this->effects,
            'meta' => $this->meta,
        ];
    }
}
