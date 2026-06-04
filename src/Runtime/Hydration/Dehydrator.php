<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Hydration;

use ReflectionObject;
use ReflectionProperty;
use VoltStack\Runtime\Component\Component;
use VoltStack\Runtime\Protocol\Checksum;

final class Dehydrator
{
    public function __construct(private readonly Checksum $checksum) {}

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
        return $this->checksum->sign($component, $state, $meta);
    }
}