<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

final class ActionRuntimePolicyBuilder
{
    private ?string $target = null;

    private ?string $action = null;

    public function __construct(
        private readonly ActionEffectOptions $options,
    ) {}

    public function onTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function clearScope(): self
    {
        $this->target = null;
        $this->action = null;

        return $this;
    }

    public function forAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function forSave(): self
    {
        return $this->forAction('save');
    }

    public function forSubmit(): self
    {
        return $this->forAction('submit');
    }

    public function forCreate(): self
    {
        return $this->forAction('create');
    }

    public function forUpdate(): self
    {
        return $this->forAction('update');
    }

    public function forDelete(): self
    {
        return $this->forAction('delete');
    }

    public function forIncrement(): self
    {
        return $this->forAction('increment');
    }

    public function forDecrement(): self
    {
        return $this->forAction('decrement');
    }

    /**
     * @param array<string, mixed> $policy
     */
    public function runtimePolicy(string $state, array $policy = []): self
    {
        $this->options->runtimePolicy($state, $policy, $this->action, $this->target);

        return $this;
    }

    public function loading(string|int|float|null $delay = null, string|int|float|null $minDuration = null): self
    {
        $this->options->loadingPolicy($delay, $minDuration, $this->action, $this->target);

        return $this;
    }

    public function success(string|int|float|null $timeout = null, string|int|float|null $minDuration = null): self
    {
        $this->options->successPolicy($timeout, $minDuration, $this->action, $this->target);

        return $this;
    }

    public function error(string|int|float|null $timeout = null): self
    {
        $this->options->errorPolicy($timeout, $this->action, $this->target);

        return $this;
    }

    public function dirty(string|int|float|null $debounce = null): self
    {
        $this->options->dirtyPolicy($debounce, $this->action, $this->target);

        return $this;
    }

    public function end(): ActionEffectOptions
    {
        return $this->options;
    }
}
