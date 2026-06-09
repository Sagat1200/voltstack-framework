<?php

declare(strict_types=1);

namespace Quantum\View\Exceptions;

use RuntimeException;
use Throwable;

class TemplateCompilerException extends RuntimeException
{
    public function __construct(
        private readonly string $summary,
        private readonly ?int $sourceLine = null,
        private readonly ?int $sourceColumn = null,
        private readonly ?string $sourcePath = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($this->formatMessage(), 0, $previous);
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function sourceLine(): ?int
    {
        return $this->sourceLine;
    }

    public function sourceColumn(): ?int
    {
        return $this->sourceColumn;
    }

    public function sourcePath(): ?string
    {
        return $this->sourcePath;
    }

    public function withSourcePath(string $sourcePath): static
    {
        if ($this->sourcePath === $sourcePath) {
            return $this;
        }

        return new static(
            $this->summary,
            $this->sourceLine,
            $this->sourceColumn,
            $sourcePath,
            $this->getPrevious(),
        );
    }

    private function formatMessage(): string
    {
        $message = rtrim($this->summary, '.');

        if ($this->sourceLine !== null && $this->sourceColumn !== null) {
            $message .= sprintf(' at line %d, column %d', $this->sourceLine, $this->sourceColumn);
        }

        if ($this->sourcePath !== null && $this->sourcePath !== '') {
            $message .= sprintf(' in [%s]', $this->sourcePath);
        }

        return $message . '.';
    }
}
