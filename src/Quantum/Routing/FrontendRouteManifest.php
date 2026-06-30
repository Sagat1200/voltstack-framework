<?php

declare(strict_types=1);

namespace Quantum\Routing;

use RuntimeException;

final class FrontendRouteManifest
{
    private const PROTOCOL_NAME = 'VoltStack Frontend Manifest';
    private const PROTOCOL_VERSION = '1.0';

    /**
     * @param array<int, array<string, mixed>> $routes
     */
    public function __construct(
        private readonly int $manifestVersion,
        private readonly string $checksum,
        private readonly array $routes,
        private readonly string $protocolVersion = self::PROTOCOL_VERSION,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $protocol = $payload['protocol'] ?? null;
        $version = $payload['version'] ?? null;
        $routes = $payload['routes'] ?? null;

        if (! is_array($protocol) || ! is_array($version) || ! is_array($routes)) {
            throw new RuntimeException('Frontend route manifest payload is invalid.');
        }

        $protocolName = $protocol['name'] ?? null;
        $protocolVersion = $protocol['version'] ?? null;
        $manifestVersion = $version['manifest'] ?? null;
        $checksum = $version['checksum'] ?? null;

        if (! is_string($protocolName) || trim($protocolName) === '' || ! is_string($protocolVersion) || trim($protocolVersion) === '') {
            throw new RuntimeException('Frontend route manifest protocol payload is invalid.');
        }

        if (! is_int($manifestVersion) || ! is_string($checksum) || trim($checksum) === '') {
            throw new RuntimeException('Frontend route manifest version payload is invalid.');
        }

        return new self($manifestVersion, $checksum, array_values($routes), $protocolVersion);
    }

    public function manifestVersion(): int
    {
        return $this->manifestVersion;
    }

    public function checksum(): string
    {
        return $this->checksum;
    }

    public function protocolName(): string
    {
        return self::PROTOCOL_NAME;
    }

    public function protocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @return array{
     *     protocol: array{name: string, version: string},
     *     version: array{manifest: int, checksum: string},
     *     routes: array<int, array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'protocol' => [
                'name' => self::PROTOCOL_NAME,
                'version' => $this->protocolVersion,
            ],
            'version' => [
                'manifest' => $this->manifestVersion,
                'checksum' => $this->checksum,
            ],
            'routes' => $this->routes,
        ];
    }
}
