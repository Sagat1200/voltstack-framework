<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

final class ActionEffectOptions
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $rules = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $effects = [];

    private ?string $scopedTarget = null;

    private ?string $scopedSelector = null;

    public static function make(): self
    {
        return new self();
    }

    public function forType(string $type): ActionEffectMatch
    {
        return new ActionEffectMatch($this, type: $type);
    }

    public function forTarget(string $target): ActionEffectMatch
    {
        return new ActionEffectMatch($this, target: $target);
    }

    public function forSelector(string $selector): ActionEffectMatch
    {
        return new ActionEffectMatch($this, selector: $selector);
    }

    public function onTarget(string $target): self
    {
        $this->scopedTarget = $target;
        $this->scopedSelector = null;

        return $this;
    }

    public function onSelector(string $selector): self
    {
        $this->scopedSelector = $selector;
        $this->scopedTarget = null;

        return $this;
    }

    public function clearScope(): self
    {
        $this->scopedTarget = null;
        $this->scopedSelector = null;

        return $this;
    }

    /**
     * @param callable(self): (self|void) $callback
     */
    public function group(callable $callback): self
    {
        $previousTarget = $this->scopedTarget;
        $previousSelector = $this->scopedSelector;

        try {
            $result = $callback($this);

            return $result instanceof self ? $result : $this;
        } finally {
            $this->scopedTarget = $previousTarget;
            $this->scopedSelector = $previousSelector;
        }
    }

    /**
     * @param callable(self): (self|void) $callback
     */
    public function batch(callable $callback): self
    {
        return $this->group($callback);
    }

    /**
     * Allows a typed callback to configure manual effects with IDE-friendly autocompletion.
     *
     * Preferred usage:
     * `->effects(fn(ActionManualEffectBuilder $effects) => $effects->onTarget('title')->focus())`
     *
     * If the callback is typed as `self`, the options instance is passed instead.
     *
     * @param (callable(self|ActionManualEffectBuilder): (self|ActionManualEffectBuilder|void))|null $callback
     */
    public function effects(?callable $callback = null): ActionManualEffectBuilder|self
    {
        $builder = new ActionManualEffectBuilder($this);

        if ($callback === null) {
            return $builder;
        }

        return $this->invokeBlock($callback, $builder);
    }

    /**
     * Allows a typed callback to configure runtime policies with IDE-friendly autocompletion.
     *
     * Preferred usage:
     * `->policies(fn(ActionRuntimePolicyBuilder $policies) => $policies->forSave()->success())`
     *
     * If the callback is typed as `self`, the options instance is passed instead.
     *
     * @param (callable(self|ActionRuntimePolicyBuilder): (self|ActionRuntimePolicyBuilder|void))|null $callback
     */
    public function policies(?callable $callback = null): ActionRuntimePolicyBuilder|self
    {
        $builder = new ActionRuntimePolicyBuilder($this);

        if ($callback === null) {
            return $builder;
        }

        return $this->invokeBlock($callback, $builder);
    }

    public function when(string $type): ActionEffectMatch
    {
        return new ActionEffectMatch(
            $this,
            type: $type,
            target: $this->scopedTarget,
            selector: $this->scopedSelector,
        );
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function transition(
        array $transition,
        ?string $type = null,
        ?string $target = null,
        ?string $selector = null,
    ): self {
        $this->rules[] = [
            'type' => $type,
            'target' => $target,
            'selector' => $selector,
            'transition' => $transition,
        ];

        return $this;
    }

    /**
     * Allows a typed callback to configure transition rules with IDE-friendly autocompletion.
     *
     * Preferred usage:
     * `->transitions(fn(ActionTransitionBuilder $transitions) => $transitions->onTarget('count')->forTextUpdate()->pop())`
     *
     * If the callback is typed as `self`, the options instance is passed instead.
     *
     * @param array<string, mixed>|(callable(self|ActionTransitionBuilder): (self|ActionTransitionBuilder|void))|null $transitions
     */
    public function transitions(
        array|callable|null $transitions = null,
        ?string $type = null,
        ?string $target = null,
        ?string $selector = null,
    ): ActionTransitionBuilder|self {
        if ($transitions === null) {
            return new ActionTransitionBuilder($this);
        }

        if (is_callable($transitions)) {
            return $this->invokeBlock($transitions, new ActionTransitionBuilder($this));
        }

        $this->rules[] = [
            'type' => $type,
            'target' => $target,
            'selector' => $selector,
            'transitions' => $transitions,
        ];

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * @param array<string, mixed> $effect
     */
    public function push(array $effect): self
    {
        $this->effects[] = $effect;

        return $this;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function effect(string $type, array $payload = []): self
    {
        $this->effects[] = ['type' => $type, ...$payload];

        return $this;
    }

    public function text(string $value, ?string $target = null, ?string $selector = null): self
    {
        return $this->effect('text.update', [
            ...$this->effectTargetPayload($target, $selector),
            'value' => $value,
        ]);
    }

    public function replace(string $html, ?string $target = null, ?string $selector = null, bool $outer = true): self
    {
        return $this->effect('html.replace', [
            ...$this->effectTargetPayload($target, $selector),
            'html' => $html,
            'outer' => $outer,
        ]);
    }

    public function append(string $html, ?string $target = null, ?string $selector = null, string $position = 'beforeend'): self
    {
        return $this->effect('dom.append', [
            ...$this->effectTargetPayload($target, $selector),
            'html' => $html,
            'position' => $position,
        ]);
    }

    public function insert(
        string $html,
        string $beforeSelector,
        ?string $target = null,
        ?string $selector = null,
        string $position = 'beforebegin',
    ): self {
        return $this->effect('dom.insert', [
            ...$this->effectTargetPayload($target, $selector),
            'html' => $html,
            'beforeSelector' => $beforeSelector,
            'position' => $position,
        ]);
    }

    public function remove(?string $target = null, ?string $selector = null): self
    {
        return $this->effect('dom.remove', $this->effectTargetPayload($target, $selector));
    }

    public function move(
        string $parentTarget,
        ?string $target = null,
        ?string $selector = null,
        ?string $beforeSelector = null,
        string $position = 'beforeend',
    ): self {
        return $this->effect('dom.move', [
            ...$this->effectTargetPayload($target, $selector),
            'parentTarget' => $parentTarget,
            'beforeSelector' => $beforeSelector,
            'position' => $position,
        ]);
    }

    public function setAttribute(string $name, string $value = '', ?string $target = null, ?string $selector = null): self
    {
        return $this->effect('attribute.set', [
            ...$this->effectTargetPayload($target, $selector),
            'name' => $name,
            'value' => $value,
        ]);
    }

    public function removeAttribute(string $name, ?string $target = null, ?string $selector = null): self
    {
        return $this->effect('attribute.remove', [
            ...$this->effectTargetPayload($target, $selector),
            'name' => $name,
        ]);
    }

    public function toggleClass(string $class, bool $force, ?string $target = null, ?string $selector = null): self
    {
        return $this->effect('class.toggle', [
            ...$this->effectTargetPayload($target, $selector),
            'class' => $class,
            'force' => $force,
        ]);
    }

    public function setStyle(string $property, ?string $value, ?string $target = null, ?string $selector = null): self
    {
        return $this->effect('style.set', [
            ...$this->effectTargetPayload($target, $selector),
            'property' => $property,
            'value' => $value,
        ]);
    }

    public function focus(?string $target = null, ?string $selector = null): self
    {
        return $this->effect('focus', $this->effectTargetPayload($target, $selector));
    }

    public function blur(?string $target = null, ?string $selector = null): self
    {
        return $this->effect('blur', $this->effectTargetPayload($target, $selector));
    }

    public function navigate(string $url, bool $replace = false, bool $preserveScroll = false): self
    {
        return $this->effect('navigate', [
            'url' => $url,
            'replace' => $replace,
            'preserveScroll' => $preserveScroll,
        ]);
    }

    /**
     * @param array<string, mixed> $detail
     */
    public function dispatch(string $event, array $detail = [], ?string $target = null, ?string $selector = null): self
    {
        return $this->effect('dispatch.event', [
            ...$this->effectTargetPayload($target, $selector),
            'event' => $event,
            'detail' => $detail,
        ]);
    }

    /**
     * @param array<string, mixed> $detail
     */
    public function event(string $event, array $detail = [], ?string $target = null, ?string $selector = null): self
    {
        return $this->dispatch($event, $detail, $target, $selector);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function scroll(array $options = [], ?string $target = null, ?string $selector = null): self
    {
        return $this->effect('scroll', [
            ...$this->effectTargetPayload($target, $selector),
            ...$options,
        ]);
    }

    /**
     * @param array<string, mixed> $policy
     */
    public function runtimePolicy(
        string $state,
        array $policy = [],
        ?string $action = null,
        ?string $target = null,
    ): self {
        $payload = ['state' => $state];
        $scopedTarget = $target ?? $this->scopedTarget;

        if (is_string($action) && $action !== '') {
            $payload['scopeAction'] = $action;
        }

        if (is_string($scopedTarget) && $scopedTarget !== '') {
            $payload['scopeTarget'] = $scopedTarget;
        }

        foreach (['delay', 'timeout', 'debounce', 'minDuration'] as $key) {
            if (! array_key_exists($key, $policy) || $policy[$key] === null) {
                continue;
            }

            $payload[$key] = $policy[$key];
        }

        if (count($payload) === 1) {
            return $this;
        }

        return $this->effect('runtime.policy', $payload);
    }

    public function loadingPolicy(
        string|int|float|null $delay = null,
        string|int|float|null $minDuration = null,
        ?string $action = null,
        ?string $target = null,
    ): self {
        return $this->runtimePolicy('loading', [
            'delay' => $delay,
            'minDuration' => $minDuration,
        ], $action, $target);
    }

    public function successPolicy(
        string|int|float|null $timeout = null,
        string|int|float|null $minDuration = null,
        ?string $action = null,
        ?string $target = null,
    ): self {
        return $this->runtimePolicy('success', [
            'timeout' => $timeout,
            'minDuration' => $minDuration,
        ], $action, $target);
    }

    public function errorPolicy(
        string|int|float|null $timeout = null,
        ?string $action = null,
        ?string $target = null,
    ): self {
        return $this->runtimePolicy('error', [
            'timeout' => $timeout,
        ], $action, $target);
    }

    public function dirtyPolicy(
        string|int|float|null $debounce = null,
        ?string $action = null,
        ?string $target = null,
    ): self {
        return $this->runtimePolicy('dirty', [
            'debounce' => $debounce,
        ], $action, $target);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function manualEffects(): array
    {
        return $this->effects;
    }

    /**
     * @param callable(self|object): (self|object|void) $callback
     */
    private function invokeBlock(callable $callback, object $preferred): self
    {
        return $this->group(function (self $options) use ($callback, $preferred): self {
            $argument = $this->callbackPrefersOptions($callback) ? $options : $preferred;
            $result = $callback($argument);

            if ($result instanceof self) {
                return $result;
            }

            return $options;
        });
    }

    private function callbackPrefersOptions(callable $callback): bool
    {
        $reflection = new \ReflectionFunction(\Closure::fromCallable($callback));
        $parameters = $reflection->getParameters();

        if ($parameters === []) {
            return false;
        }

        $type = $parameters[0]->getType();

        if ($type === null) {
            return false;
        }

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName() === self::class;
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $namedType) {
                if ($namedType->getName() === self::class) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function effectTargetPayload(?string $target, ?string $selector): array
    {
        if ($target === null && $selector === null) {
            $target = $this->scopedTarget;
            $selector = $this->scopedSelector;
        }

        if (is_string($selector) && $selector !== '') {
            return ['selector' => $selector];
        }

        if (is_string($target) && $target !== '') {
            return ['target' => $target];
        }

        return [];
    }
}
