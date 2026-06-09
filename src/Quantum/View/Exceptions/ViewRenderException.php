<?php

declare(strict_types=1);

namespace Quantum\View\Exceptions;

use RuntimeException;
use Throwable;

final class ViewRenderException extends RuntimeException
{
    public function __construct(
        private readonly string $viewPath,
        ?Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Unable to render view [%s].', $viewPath), 0, $previous);
    }

    public function viewPath(): string
    {
        return $this->viewPath;
    }
}
