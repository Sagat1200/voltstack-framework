<?php

declare(strict_types=1);

namespace Quantum\Cache\Contracts;

use DateInterval;
use DateTimeInterface;

interface StoreInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value, DateInterval|DateTimeInterface|int|null $ttl = null): bool;

    public function forever(string $key, mixed $value): bool;

    public function has(string $key): bool;

    public function forget(string $key): bool;

    public function flush(): bool;
}
