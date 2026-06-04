<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Component;

use Quantum\Http\Request;
use Quantum\Validation\Validator;
use Quantum\View\View;

abstract class Component
{
    protected ?Request $request = null;

    abstract public function render(): View|string;

    public function setRequest(?Request $request): void
    {
        $this->request = $request;
    }

    public function request(): ?Request
    {
        return $this->request;
    }

    protected function view(string $name, array $data = []): View
    {
        return view($name, $data);
    }

    protected function validate(array $data, array $rules): array
    {
        return app(Validator::class)->validate($data, $rules);
    }
}
