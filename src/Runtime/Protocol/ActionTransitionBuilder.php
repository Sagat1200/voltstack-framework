<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

final class ActionTransitionBuilder
{
    private ?string $target = null;

    private ?string $selector = null;

    private ?string $type = null;

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

    public function when(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function forType(string $type): self
    {
        return $this->when($type);
    }

    public function forTextUpdate(): self
    {
        return $this->when('text.update');
    }

    public function forHtmlReplace(): self
    {
        return $this->when('html.replace');
    }

    public function forAppend(): self
    {
        return $this->when('dom.append');
    }

    public function forInsert(): self
    {
        return $this->when('dom.insert');
    }

    public function forRemove(): self
    {
        return $this->when('dom.remove');
    }

    public function forMove(): self
    {
        return $this->when('dom.move');
    }

    public function forAttributeSet(): self
    {
        return $this->when('attribute.set');
    }

    public function forAttributeRemove(): self
    {
        return $this->when('attribute.remove');
    }

    public function forClassToggle(): self
    {
        return $this->when('class.toggle');
    }

    public function forStyleSet(): self
    {
        return $this->when('style.set');
    }

    public function named(string $name, ?int $duration = null, ?string $className = null): self
    {
        return $this->transition($this->namedTransition($name, $duration, $className));
    }

    public function fade(?int $duration = null, ?string $className = null): self
    {
        return $this->named('fade', $duration, $className);
    }

    public function pop(?int $duration = null, ?string $className = null): self
    {
        return $this->named('pop', $duration, $className);
    }

    public function glow(?int $duration = null, ?string $className = null): self
    {
        return $this->named('glow', $duration, $className);
    }

    public function pulse(?int $duration = null, ?string $className = null): self
    {
        return $this->named('pulse', $duration, $className);
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function transition(array $transition): self
    {
        $this->options->transition(
            $transition,
            type: $this->type,
            target: $this->target,
            selector: $this->selector,
        );

        return $this;
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function phase(string $phase, array $transition): self
    {
        return $this->transitions([$phase => $transition]);
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function enter(array $transition): self
    {
        return $this->phase('enter', $transition);
    }

    public function enterAs(string $name, ?int $duration = null, ?string $className = null): self
    {
        return $this->enter($this->namedTransition($name, $duration, $className));
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function update(array $transition): self
    {
        return $this->phase('update', $transition);
    }

    public function updateAs(string $name, ?int $duration = null, ?string $className = null): self
    {
        return $this->update($this->namedTransition($name, $duration, $className));
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function move(array $transition): self
    {
        return $this->phase('move', $transition);
    }

    public function moveAs(string $name, ?int $duration = null, ?string $className = null): self
    {
        return $this->move($this->namedTransition($name, $duration, $className));
    }

    /**
     * @param array<string, mixed> $transition
     */
    public function leave(array $transition): self
    {
        return $this->phase('leave', $transition);
    }

    public function leaveAs(string $name, ?int $duration = null, ?string $className = null): self
    {
        return $this->leave($this->namedTransition($name, $duration, $className));
    }

    /**
     * @param array<string, mixed> $transitions
     */
    public function transitions(array $transitions): self
    {
        $this->options->transitions(
            $transitions,
            type: $this->type,
            target: $this->target,
            selector: $this->selector,
        );

        return $this;
    }

    public function end(): ActionEffectOptions
    {
        return $this->options;
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