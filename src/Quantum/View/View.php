<?php

declare(strict_types=1);

namespace Quantum\View;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly ViewFactory $factory,
        private readonly string $name,
        private array $data = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function with(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->data[$key] = $value;

        return $clone;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function render(): string
    {
        return $this->factory->render($this->name, $this->data);
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
