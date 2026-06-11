<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

final class ActionManualEffectBuilder
{
    private ?string $target = null;

    private ?string $selector = null;

    public function __construct(
        private readonly ActionEffectOptions $options,
    ) {}

    public function onTarget(string $target): self
    {
        $this->target = $target;
        $this->selector = null;

        return $this;
    }

    public function onSelector(string $selector): self
    {
        $this->selector = $selector;
        $this->target = null;

        return $this;
    }

    public function clearScope(): self
    {
        $this->target = null;
        $this->selector = null;

        return $this;
    }

    /**
     * @param array<string, mixed> $effect
     */
    public function push(array $effect): self
    {
        $this->options->push($effect);

        return $this;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function effect(string $type, array $payload = []): self
    {
        $this->options->effect($type, [...$this->targetPayload(), ...$payload]);

        return $this;
    }

    public function text(string $value): self
    {
        $this->options->text($value, $this->target, $this->selector);

        return $this;
    }

    public function replace(string $html, bool $outer = true): self
    {
        $this->options->replace($html, $this->target, $this->selector, $outer);

        return $this;
    }

    public function append(string $html, string $position = 'beforeend'): self
    {
        $this->options->append($html, $this->target, $this->selector, $position);

        return $this;
    }

    public function insert(string $html, string $beforeSelector, string $position = 'beforebegin'): self
    {
        $this->options->insert($html, $beforeSelector, $this->target, $this->selector, $position);

        return $this;
    }

    public function remove(): self
    {
        $this->options->remove($this->target, $this->selector);

        return $this;
    }

    public function move(string $parentTarget, ?string $beforeSelector = null, string $position = 'beforeend'): self
    {
        $this->options->move($parentTarget, $this->target, $this->selector, $beforeSelector, $position);

        return $this;
    }

    public function setAttribute(string $name, string $value = ''): self
    {
        $this->options->setAttribute($name, $value, $this->target, $this->selector);

        return $this;
    }

    public function removeAttribute(string $name): self
    {
        $this->options->removeAttribute($name, $this->target, $this->selector);

        return $this;
    }

    public function toggleClass(string $class, bool $force): self
    {
        $this->options->toggleClass($class, $force, $this->target, $this->selector);

        return $this;
    }

    public function setStyle(string $property, ?string $value): self
    {
        $this->options->setStyle($property, $value, $this->target, $this->selector);

        return $this;
    }

    public function focus(): self
    {
        $this->options->focus($this->target, $this->selector);

        return $this;
    }

    public function focusAndSetAttribute(string $name, string $value = ''): self
    {
        return $this->focus()->setAttribute($name, $value);
    }

    public function focusAndRemoveAttribute(string $name): self
    {
        return $this->focus()->removeAttribute($name);
    }

    /**
     * @param array<string, mixed> $detail
     */
    public function focusAndEvent(string $event, array $detail = []): self
    {
        return $this->focus()->event($event, $detail);
    }

    public function blur(): self
    {
        $this->options->blur($this->target, $this->selector);

        return $this;
    }

    /**
     * @param array<string, mixed> $detail
     */
    public function blurAndEvent(string $event, array $detail = []): self
    {
        return $this->blur()->event($event, $detail);
    }

    public function navigate(string $url, bool $replace = false, bool $preserveScroll = false): self
    {
        $this->options->navigate($url, $replace, $preserveScroll);

        return $this;
    }

    /**
     * @param array<string, mixed> $detail
     */
    public function dispatch(string $event, array $detail = []): self
    {
        $this->options->dispatch($event, $detail, $this->target, $this->selector);

        return $this;
    }

    /**
     * @param array<string, mixed> $detail
     */
    public function event(string $event, array $detail = []): self
    {
        $this->options->event($event, $detail, $this->target, $this->selector);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function scroll(array $options = []): self
    {
        $this->options->scroll($options, $this->target, $this->selector);

        return $this;
    }

    public function scrollIntoView(
        ?string $behavior = null,
        ?string $block = null,
        ?string $inline = null,
    ): self {
        $options = array_filter([
            'behavior' => $behavior,
            'block' => $block,
            'inline' => $inline,
        ], static fn(mixed $value): bool => $value !== null);

        return $this->scroll($options);
    }

    public function end(): ActionEffectOptions
    {
        return $this->options;
    }

    /**
     * @return array<string, string>
     */
    private function targetPayload(): array
    {
        if ($this->selector !== null && $this->selector !== '') {
            return ['selector' => $this->selector];
        }

        if ($this->target !== null && $this->target !== '') {
            return ['target' => $this->target];
        }

        return [];
    }
}
