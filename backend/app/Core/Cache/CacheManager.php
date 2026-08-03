<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

use PaginiumCMS\Core\Cache\Contracts\CacheDriverInterface;
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
    private CacheDriverInterface|DriverInterface $driver;
    private string $prefix;
    private string $lockPath;
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(CacheDriverInterface|DriverInterface $driver, string $prefix = 'paginium_', ?string $lockPath = null)
    {
        $this->driver = $driver;
        $this->prefix = $prefix;
        $this->lockPath = $lockPath ?? sys_get_temp_dir() . '/paginium_cache_locks';
        if (!is_dir($this->lockPath)) {
            @mkdir($this->lockPath, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->driver->get($this->prefix . $key, $default);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
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

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if ($this->has($key)) {
            ++$this->hits;

            return $this->get($key);
        }

        ++$this->misses;
        $value = $callback();
        if ($value !== null) {
            $this->set($key, $value, $ttl);
        }

        return $value;
    }

    /**
     * remember() s file lock – len jeden proces regeneruje hodnotu.
     */
    public function rememberLocked(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if ($this->has($key)) {
            ++$this->hits;

            return $this->get($key);
        }

        $lockFile = $this->lockPath . '/' . hash('sha256', $this->prefix . $key) . '.lock';
        $handle = fopen($lockFile, 'c+');
        if ($handle === false) {
            return $this->remember($key, $callback, $ttl);
        }

        $sentinel = new \stdClass();

        try {
            flock($handle, LOCK_EX);
            $cached = $this->driver->get($this->prefix . $key, $sentinel);
            if ($cached !== $sentinel) {
                ++$this->hits;

                return $cached;
            }

            ++$this->misses;
            $value = $callback();
            if ($value !== null) {
                $this->set($key, $value, $ttl);
            }

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

    /**
     * @param list<string> $tags
     */
    public function invalidateTags(array $tags): int
    {
        if ($this->driver instanceof CacheDriverInterface) {
            return $this->driver->invalidateTags($tags);
        }

        return 0;
    }

    /**
     * @param list<string> $tags
     */
    public function tagKey(string $key, array $tags): void
    {
        if ($this->driver instanceof CacheDriverInterface) {
            $this->driver->tagKey($this->prefix . $key, $tags);
        }
    }

    /**
     * @return array{hits: int, misses: int}
     */
    public function metrics(): array
    {
        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
        ];
    }
}
