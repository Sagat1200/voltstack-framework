<?php

declare(strict_types=1);

namespace Quantum\View;

use Quantum\View\Exceptions\ViewNotFoundException;

final class ViewFactory
{
    /**
     * @param array<int, string> $paths
     */
    public function __construct(
        private readonly PhpViewEngine $engine,
        private array $paths = [],
    ) {
    }

    /**
     * @param array<int, string> $paths
     */
    public function setPaths(array $paths): void
    {
        $this->paths = $paths;
    }

    /**
     * @return array<int, string>
     */
    public function paths(): array
    {
        return $this->paths;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function make(string $view, array $data = []): View
    {
        return new View($this, $view, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        return $this->engine->render($this->find($view), $data);
    }

    public function exists(string $view): bool
    {
        try {
            $this->find($view);

            return true;
        } catch (ViewNotFoundException) {
            return false;
        }
    }

    public function find(string $view): string
    {
        $relativePath = str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';

        foreach ($this->paths as $path) {
            $candidate = rtrim($path, '\\/') . DIRECTORY_SEPARATOR . $relativePath;

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new ViewNotFoundException(sprintf('View [%s] was not found.', $view));
    }
}
