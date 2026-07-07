<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Component\Exceptions;

use RuntimeException;
use Throwable;

final class ComponentMountException extends RuntimeException
{
    public static function forComponent(string $component, Throwable $previous): self
    {
        return new self(
            sprintf('Component [%s] failed during mount.', $component),
            0,
            $previous,
        );
    }
}
