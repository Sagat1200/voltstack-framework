<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Hydration;

use Quantum\Http\Request;
use ReflectionObject;
use ReflectionProperty;
use VoltStack\Runtime\Component\Component;
use VoltStack\Runtime\Hydration\Exceptions\InvalidSnapshotException;

final class Hydrator
{
    public function __construct(private readonly Dehydrator $dehydrator)
    {
    }

    /**
     * @param array<string, mixed>|Snapshot $snapshot
     */
    public function hydrate(Component $component, array|Snapshot $snapshot, ?Request $request = null): Component
    {
        $snapshot = is_array($snapshot) ? Snapshot::fromArray($snapshot) : $snapshot;

        if ($snapshot->component() !== $component::class) {
            throw new InvalidSnapshotException('Snapshot component does not match the target component.');
        }

        $expectedChecksum = $this->dehydrator->checksum(
            $snapshot->component(),
            $snapshot->state(),
            $snapshot->meta(),
        );

        if (! hash_equals($expectedChecksum, $snapshot->checksum())) {
            throw new InvalidSnapshotException('Snapshot checksum is invalid.');
        }

        $reflection = new ReflectionObject($component);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();

            if (array_key_exists($name, $snapshot->state())) {
                $property->setValue($component, $snapshot->state()[$name]);
            }
        }

        $component->setRequest($request);

        return $component;
    }
}
