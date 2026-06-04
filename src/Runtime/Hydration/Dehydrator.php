<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Hydration;

use ReflectionObject;
use ReflectionProperty;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\Component;

final class Dehydrator
{
    public function __construct(private readonly Application $app)
    {
    }

    public function dehydrate(Component $component, array $meta = []): Snapshot
    {
        $state = [];
        $reflection = new ReflectionObject($component);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $state[$property->getName()] = $property->getValue($component);
        }

        return new Snapshot(
            $component::class,
            $state,
            $this->checksum($component::class, $state, $meta),
            $meta,
        );
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $meta
     */
    public function checksum(string $component, array $state, array $meta = []): string
    {
        $payload = json_encode([
            'component' => $component,
            'state' => $state,
            'meta' => $meta,
        ], JSON_THROW_ON_ERROR);

        return hash_hmac('sha256', $payload, $this->secret());
    }

    private function secret(): string
    {
        $secret = (string) $this->app->config('app.key', '');

        if ($secret !== '') {
            return $secret;
        }

        return 'voltstack|' . $this->app->basePath();
    }
}
