<?php

declare(strict_types=1);

namespace Quantum\View\Runtime;

use Quantum\View\ViewFactory;
use RuntimeException;

final class ViewRuntime
{
    /**
     * @var array<string, string>
     */
    private array $sections = [];

    /**
     * @var array<int, string>
     */
    private array $sectionStack = [];

    private ?string $parentView = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly ViewFactory $factory,
        private readonly array $data = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $runtime = clone $this;
        $payload = array_merge($this->data, $data);
        $runtime->parentView = null;

        return $this->factory->renderWithRuntime($view, $payload, $runtime);
    }

    public function extend(string $view): void
    {
        $this->parentView = $view;
    }

    public function hasParentView(): bool
    {
        return is_string($this->parentView) && trim($this->parentView) !== '';
    }

    public function renderParent(): string
    {
        if (! $this->hasParentView()) {
            return '';
        }

        $runtime = clone $this;
        $runtime->parentView = null;

        return $this->factory->renderWithRuntime($this->parentView, $this->data, $runtime);
    }

    public function startSection(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = array_pop($this->sectionStack);

        if (! is_string($name)) {
            throw new RuntimeException('Unable to end a section that was not started.');
        }

        $this->sections[$name] = (string) ob_get_clean();
    }

    public function yieldContent(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }
}
