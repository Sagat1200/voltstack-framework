<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

final class ActionEffectMatch
{
    public function __construct(
        private readonly ActionEffectOptions $options,
        private ?string $type = null,
        private ?string $target = null,
        private ?string $selector = null,
    ) {}

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function target(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function selector(string $selector): self
    {
        $this->selector = $selector;

        return $this;
    }

    public function named(string $name, ?int $duration = null, ?string $className = null): ActionEffectOptions
    {
        return $this->transition($this->namedTransition($name, $duration, $className));
    }

    public function fade(?int $duration = null, ?string $className = null): ActionEffectOptions
    {
        return $this->named('fade', $duration, $className);
    }

    public function pop(?int $duration = null, ?string $className = null): ActionEffectOptions
    {
        return $this->named('pop', $duration, $className);
    }

    public function glow(?int $duration = null, ?string $className = null): ActionEffectOptions
    {
        return $this->named('glow', $duration, $className);
    }

    public function pulse(?int $duration = null, ?string $className = null): ActionEffectOptions
    {
        return $this->named('pulse', $duration, $className);
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function transition(array $transition): ActionEffectOptions
    {
        return $this->options->transition(
            $transition,
            type: $this->type,
            target: $this->target,
            selector: $this->selector,
        );
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function phase(string $phase, array $transition): ActionEffectOptions
    {
        return $this->transitions([$phase => $transition]);
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function enter(array $transition): ActionEffectOptions
    {
        return $this->phase('enter', $transition);
    }

    public function enterAs(string $name, ?int $duration = null, ?string $className = null): ActionEffectOptions
    {
        return $this->enter($this->namedTransition($name, $duration, $className));
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function update(array $transition): ActionEffectOptions
    {
        return $this->phase('update', $transition);
    }

    public function updateAs(string $name, ?int $duration = null, ?string $className = null): ActionEffectOptions
    {
        return $this->update($this->namedTransition($name, $duration, $className));
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function move(array $transition): ActionEffectOptions
    {
        return $this->phase('move', $transition);
    }

    public function moveAs(string $name, ?int $duration = null, ?string $className = null): ActionEffectOptions
    {
        return $this->move($this->namedTransition($name, $duration, $className));
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function leave(array $transition): ActionEffectOptions
    {
        return $this->phase('leave', $transition);
    }

    public function leaveAs(string $name, ?int $duration = null, ?string $className = null): ActionEffectOptions
    {
        return $this->leave($this->namedTransition($name, $duration, $className));
    }

    /**
     * @param array<string, mixed> $transitions
     */
    public function transitions(array $transitions): ActionEffectOptions
    {
        return $this->options->transitions(
            $transitions,
            type: $this->type,
            target: $this->target,
            selector: $this->selector,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function namedTransition(string $name, ?int $duration = null, ?string $className = null): array
    {
        $transition = ['name' => $name];

        if ($duration !== null) {
            $transition['duration'] = $duration;
        }

        if ($className !== null && $className !== '') {
            $transition['className'] = $className;
        }

        return $transition;
    }
}
