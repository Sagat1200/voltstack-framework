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
    ) {
    }

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
}
