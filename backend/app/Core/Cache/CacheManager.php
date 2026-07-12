<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

use PaginiumCMS\Core\Cache\Drivers\DriverInterface;

class CacheManager
{
    private DriverInterface $driver;
    private string $prefix;

    public function __construct(DriverInterface $driver, string $prefix = 'paginium_')
    {
        $this->driver = $driver;
        $this->prefix = $prefix;
    }

    public function get(string $key, $default = null)
    {
        return $this->driver->get($this->prefix . $key, $default);
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        return $this->driver->set($this->prefix . $key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->driver->delete($this->prefix . $key);
    }

    public function clear(): bool
    {
        return $this->driver->clear();
    }

    public function has(string $key): bool
    {
        return $this->driver->has($this->prefix . $key);
    }

    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }
}
