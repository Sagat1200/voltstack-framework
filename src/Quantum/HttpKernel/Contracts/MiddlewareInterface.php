<?php

declare(strict_types=1);

namespace Quantum\HttpKernel\Contracts;

use Closure;
use Quantum\Http\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, Closure $next): mixed;
}
