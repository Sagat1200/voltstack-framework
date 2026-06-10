<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

final class ActionEffectOptions
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $rules = [];

    public static function make(): self
    {
        return new self();
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
     * @param array<string, mixed> $transitions
     */
    public function transitions(
        array $transitions,
        ?string $type = null,
        ?string $target = null,
        ?string $selector = null,
    ): self {
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
}
