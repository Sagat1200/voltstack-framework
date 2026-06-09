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
    private const RENDER_MODE_SERVER = 'server';

    private const RENDER_MODE_INTERACTIVE = 'interactive';

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

    private string|Component|null $parentComponent = null;

    /**
     * @var array<string, mixed>
     */
    private array $parentComponentProps = [];

    private string $renderMode = self::RENDER_MODE_SERVER;

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

    public function setRenderMode(string $mode): void
    {
        $mode = strtolower(trim($mode));

        if (! in_array($mode, [self::RENDER_MODE_SERVER, self::RENDER_MODE_INTERACTIVE], true)) {
            throw new RuntimeException(sprintf(
                'Unsupported render mode [%s]. Expected one of: server, interactive.',
                $mode === '' ? 'empty' : $mode,
            ));
        }

        $this->renderMode = $mode;
    }

    public function renderMode(): string
    {
        return $this->renderMode;
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

    /**
     * @param array<string, mixed> $props
     */
    public function extendComponent(string|Component $component, array $props = []): void
    {
        $this->parentComponent = $component;
        $this->parentComponentProps = $props;
    }

    public function hasParentComponent(): bool
    {
        return is_string($this->parentComponent) || $this->parentComponent instanceof Component;
    }

    public function renderParentComponent(string $slot): string
    {
        if (! $this->hasParentComponent()) {
            return $slot;
        }

        $component = $this->parentComponent;
        $props = $this->parentComponentProps;
        $this->parentComponent = null;
        $this->parentComponentProps = [];

        if (! is_string($component) && ! $component instanceof Component) {
            return $slot;
        }

        return $this->renderDynamicComponent($component, [
            ...$props,
            'slot' => $slot,
        ]);
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
            'render_mode' => $this->renderMode,
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
        $renderMode = is_string($entry['render_mode'] ?? null) ? $entry['render_mode'] : self::RENDER_MODE_SERVER;

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

        return $this->renderMountedComponent($instance, $renderMode);
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

        return $this->renderMountedComponent($instance, $this->renderMode);
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

    private function renderMountedComponent(Component $component, string $mode): string
    {
        $manager = app(ComponentManager::class);

        return $mode === self::RENDER_MODE_INTERACTIVE
            ? $manager->renderRoot($component, null, $mode)
            : $manager->render($component);
    }
}