<?php

declare(strict_types=1);

namespace Quantum\Routing;

use RuntimeException;

final class VersionArtifact
{
    /**
     * @param array<string, array{version: int, checksum: string}> $artifacts
     */
    public function __construct(
        private readonly int $version,
        private readonly array $artifacts,
    ) {}

    public static function fromArray(array $payload): self
    {
        $version = $payload['version'] ?? null;
        $artifacts = $payload['artifacts'] ?? null;

        if (! is_int($version) || ! is_array($artifacts)) {
            throw new RuntimeException('Version artifact payload is invalid.');
        }

        return new self($version, $artifacts);
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * @return array<string, array{version: int, checksum: string}>
     */
    public function artifacts(): array
    {
        return $this->artifacts;
    }

    public function validates(string $name, string $path, int $version): bool
    {
        $entry = $this->artifacts[$name] ?? null;

        if (! is_array($entry) || ! is_int($entry['version'] ?? null) || ! is_string($entry['checksum'] ?? null)) {
            return false;
        }

        if (! is_file($path) || $entry['version'] !== $version) {
            return false;
        }

        return hash_equals($entry['checksum'], hash_file('sha256', $path) ?: '');
    }

    /**
     * @return array{version: int, artifacts: array<string, array{version: int, checksum: string}>}
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'artifacts' => $this->artifacts,
        ];
    }
}
