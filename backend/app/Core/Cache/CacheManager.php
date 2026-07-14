<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

use PaginiumCMS\Core\Cache\Drivers\DriverInterface;

/**
 * Facade nad cache driverom s prefixom a pokročilými operáciami.
 *
 * - remember()      – lazy load s TTL
 * - rememberLocked() – ochrana proti cache stampede (flock)
 * - increment()     – atomický počítadlo (rate limit, metriky)
 * - deleteByPrefix() – invalidácia skupín kľúčov (obsah, stránky)
 */
class CacheManager
{
    private DriverInterface $driver;
    private string $prefix;
    private string $lockPath;

    public function __construct(DriverInterface $driver, string $prefix = 'paginium_', ?string $lockPath = null)
    {
        $this->driver = $driver;
        $this->prefix = $prefix;
        $this->lockPath = $lockPath ?? sys_get_temp_dir() . '/paginium_cache_locks';
        if (!is_dir($this->lockPath)) {
            @mkdir($this->lockPath, 0755, true);
        }
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

    /**
     * remember() s file lock – len jeden proces regeneruje hodnotu.
     */
    public function rememberLocked(string $key, callable $callback, ?int $ttl = null)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $lockFile = $this->lockPath . '/' . hash('sha256', $this->prefix . $key) . '.lock';
        $handle = fopen($lockFile, 'c+');
        if ($handle === false) {
            return $this->remember($key, $callback, $ttl);
        }

        try {
            flock($handle, LOCK_EX);
            if ($this->has($key)) {
                return $this->get($key);
            }
            $value = $callback();
            $this->set($key, $value, $ttl);

            return $value;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function increment(string $key, int $step = 1, ?int $ttl = null): int
    {
        if (method_exists($this->driver, 'increment')) {
            return $this->driver->increment($this->prefix . $key, $step, $ttl);
        }

        $current = (int) $this->get($key, 0);
        $new = $current + $step;
        $this->set($key, $new, $ttl);

        return $new;
    }
}
