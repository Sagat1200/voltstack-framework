<?php

declare(strict_types=1);

namespace Quantum\View\Directives\Contracts;

interface DirectiveContract
{
    public function compile(?string $expression = null): string;
}