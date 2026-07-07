<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Component\Exceptions;

use RuntimeException;
use Throwable;

final class ComponentRenderException extends RuntimeException
{
    public static function forComponent(string $component, Throwable $previous): self
    {
        return new self(
            sprintf('Component [%s] failed during render.', $component),
            0,
            $previous,
        );
    }
}
