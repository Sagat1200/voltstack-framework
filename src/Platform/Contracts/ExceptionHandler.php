<?php

declare(strict_types=1);

namespace VoltStack\Framework\Contracts;

use Quantum\Http\Request;
use Quantum\Http\Response;
use Throwable;

interface ExceptionHandler
{
    public function render(Request $request, Throwable $exception): Response;
}
