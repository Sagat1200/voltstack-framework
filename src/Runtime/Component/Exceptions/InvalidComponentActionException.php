<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Component\Exceptions;

use RuntimeException;

final class InvalidComponentActionException extends RuntimeException
{
    public static function invalid(): self
    {
        return new self('Component action is not allowed.');
    }

    public static function missing(): self
    {
        return new self('Component action is not allowed.');
    }

    public static function nonPublic(): self
    {
        return new self('Component action is not allowed.');
    }

    public static function reserved(): self
    {
        return new self('Component action is not allowed.');
    }
}
