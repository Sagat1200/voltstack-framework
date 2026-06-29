<?php

declare(strict_types=1);

namespace Quantum\Routing;

use RuntimeException;

final class MetadataArtifact
{
    /**
     * @param array<int, array<string, mixed>> $metadata
     */
    public function __construct(
        private readonly int $version,
        private readonly int $routeCount,
        private readonly array $metadata,
    ) {}

    public static function fromArray(array $payload): self
    {
        $version = $payload['version'] ?? null;
        $routeCount = $payload['routeCount'] ?? null;
        $metadata = $payload['metadata'] ?? null;

        if (! is_int($version) || ! is_int($routeCount) || ! is_array($metadata)) {
            throw new RuntimeException('Metadata artifact payload is invalid.');
        }

        return new self($version, $routeCount, array_values($metadata));
    }

    public function version(): int
    {
        return $this->version;
    }

    public function routeCount(): int
    {
        return $this->routeCount;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function applyTo(CompiledRouteCollection $collection): void
    {
        $collection->applyMetadataSnapshots($this->metadata);
    }

    /**
     * @return array{version: int, routeCount: int, metadata: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'routeCount' => $this->routeCount,
            'metadata' => $this->metadata,
        ];
    }
}
