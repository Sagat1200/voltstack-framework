<?php

declare(strict_types=1);

namespace Quantum\View;

use RuntimeException;
use Throwable;

final class PhpViewEngine
{
    /**
     * @param array<string, mixed> $data
     */
    public function render(string $path, array $data = []): string
    {
        ob_start();
        extract($data, EXTR_SKIP);

        try {
            require $path;
        } catch (Throwable $exception) {
            ob_end_clean();

            throw new RuntimeException(sprintf('Unable to render view [%s].', $path), 0, $exception);
        }

        return (string) ob_get_clean();
    }
}
