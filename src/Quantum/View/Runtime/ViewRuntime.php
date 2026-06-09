<?php

declare(strict_types=1);

namespace Quantum\View\Runtime;

use Quantum\View\ViewFactory;
use RuntimeException;
use VoltStack\Runtime\Component\ComponentAttributeBag;
use VoltStack\Runtime\Component\Component;
use VoltStack\Runtime\Component\ComponentManager;

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

    /**
     * @var array<int, array{component: string|Component, props: array<string, mixed>, slots: array<string, string>}>
     */
    private array $componentStack = [];

    /**
     * @var array<int, string>
     */
    private array $slotStack = [];

    private ?string $parentView = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly ViewFactory $factory,
        private readonly array $data = [],
    ) {}

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

    /**
     * @param array<string, mixed> $props
     */
    public function startComponent(string|Component $component, array $props = []): void
    {
        $this->componentStack[] = [
            'component' => $component,
            'props' => $props,
            'slots' => [],
        ];
        ob_start();
    }

    public function endComponent(): string
    {
        $entry = array_pop($this->componentStack);

        if (! is_array($entry) || ! isset($entry['component'], $entry['props'], $entry['slots'])) {
            throw new RuntimeException('Unable to end a component that was not started.');
        }

        $component = $entry['component'];
        $props = is_array($entry['props']) ? $entry['props'] : [];
        $namedSlots = is_array($entry['slots']) ? $entry['slots'] : [];

        if (! is_string($component) && ! $component instanceof Component) {
            throw new RuntimeException('Unable to resolve the current component instance.');
        }

        $slot = (string) ob_get_clean();
        $instance = app(ComponentManager::class)->mount($component, [
            ...$props,
            ...$namedSlots,
            'slots' => $namedSlots,
            'slot' => $slot,
        ]);

        return app(ComponentManager::class)->render($instance);
    }

    public function startSlot(string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            throw new RuntimeException('Unable to start a slot with an empty name.');
        }

        if ($this->componentStack === []) {
            throw new RuntimeException('Unable to start a slot outside of a component.');
        }

        $this->slotStack[] = $name;
        ob_start();
    }

    public function endSlot(): void
    {
        $name = array_pop($this->slotStack);

        if (! is_string($name)) {
            throw new RuntimeException('Unable to end a slot that was not started.');
        }

        $index = array_key_last($this->componentStack);

        if ($index === null) {
            throw new RuntimeException('Unable to attach a slot without an active component.');
        }

        $this->componentStack[$index]['slots'][$name] = (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $props
     */
    public function renderDynamicComponent(string|Component $component, array $props = []): string
    {
        $manager = app(ComponentManager::class);
        $instance = $manager->mount($component, $props);

        return $manager->render($instance);
    }

    /**
     * @param array<array-key, mixed> $definitions
     * @return array<string, mixed>
     */
    public function normalizeProps(array $definitions): array
    {
        $props = [];

        foreach ($definitions as $key => $value) {
            if (is_int($key)) {
                if (! is_string($value) || trim($value) === '') {
                    continue;
                }

                $props[$value] = null;
                continue;
            }

            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            $props[$key] = $value;
        }

        return $props;
    }

    /**
     * @param array<array-key, mixed>|string $definitions
     */
    public function classList(array|string $definitions): string
    {
        return ComponentAttributeBag::formatClasses($definitions);
    }

    /**
     * @param array<array-key, mixed>|string $definitions
     */
    public function styleList(array|string $definitions): string
    {
        return ComponentAttributeBag::formatStyles($definitions);
    }
}
