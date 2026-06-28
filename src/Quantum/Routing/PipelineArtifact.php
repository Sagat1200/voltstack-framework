<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Quantum\HttpKernel\CompiledMiddlewarePipeline;
use RuntimeException;

final class PipelineArtifact
{
    /**
     * @param array<string, array<int, class-string>> $pipelines
     */
    public function __construct(
        private readonly int $version,
        private readonly array $pipelines,
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $version = $payload['version'] ?? null;
        $pipelines = $payload['pipelines'] ?? null;

        if (! is_int($version) || ! is_array($pipelines)) {
            throw new RuntimeException('Invalid pipeline artifact payload.');
        }

        $normalized = [];

        foreach ($pipelines as $id => $middlewares) {
            if (! is_string($id) || ! is_array($middlewares)) {
                throw new RuntimeException('Invalid pipeline artifact payload.');
            }

            $normalized[$id] = [];

            foreach ($middlewares as $middleware) {
                if (! is_string($middleware) || $middleware === '') {
                    throw new RuntimeException('Invalid pipeline artifact payload.');
                }

                $normalized[$id][] = $middleware;
            }
        }

        return new self($version, $normalized);
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * @return array<string, array<int, class-string>>
     */
    public function pipelines(): array
    {
        return $this->pipelines;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->pipelines);
    }

    /**
     * @return array<int, class-string>
     */
    public function middlewares(string $id): array
    {
        return $this->pipelines[$id] ?? [];
    }

    /**
     * @return array<string, CompiledMiddlewarePipeline>
     */
    public function compilePipelines(): array
    {
        $compiled = [];

        foreach ($this->pipelines as $id => $middlewares) {
            $compiled[$id] = CompiledMiddlewarePipeline::compile($middlewares);
        }

        return $compiled;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'pipelines' => $this->pipelines,
        ];
    }
}
