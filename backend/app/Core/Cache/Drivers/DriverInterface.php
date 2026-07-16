<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Drivers;

interface DriverInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    public function delete(string $key): bool;

    public function clear(): bool;

    public function has(string $key): bool;
}
