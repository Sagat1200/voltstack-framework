<?php

declare(strict_types=1);

namespace Quantum\View\Directives\Support;

use Closure;
use Quantum\View\Directives\Contracts\DirectiveContract;
use RuntimeException;

final class CallbackDirective implements DirectiveContract
{
    /**
     * @param Closure(?string): string $compiler
     */
    public function __construct(
        private readonly Closure $compiler,
        private readonly bool $expectsExpression = false,
    ) {
    }

    public function compile(?string $expression = null): string
    {
        if ($this->expectsExpression && ($expression === null || trim($expression) === '')) {
            throw new RuntimeException('The directive requires an expression.');
        }

        return ($this->compiler)($expression);
    }
}
