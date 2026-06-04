<?php

declare(strict_types=1);

namespace Quantum\Config;

final class ConfigRepository
{
    /**
     * @var array<string, mixed>
     */
    private array $items = [];

    /**
     * @param array<string, mixed> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function has(string $key): bool
    {
        return $this->get($key, '__voltstack_missing__') !== '__voltstack_missing__';
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->all();
        }

        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &$this->items;

        foreach ($segments as $segment) {
            if (! isset($target[$segment]) || ! is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }

        $target = $value;
    }

    public function replace(array $items): void
    {
        $this->items = $items;
    }

    public function loadPath(string $configPath): void
    {
        if (! is_dir($configPath)) {
            return;
        }

        $files = glob(rtrim($configPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php');

        if ($files === false) {
            return;
        }

        sort($files);

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $config = require $file;

            if (is_array($config)) {
                $this->items[$key] = $config;
            }
        }
    }
}
