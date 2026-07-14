<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Drivers;

/**
 * In-process cache driver (per PHP worker).
 *
 * Najrýchlejšia vrstva v reťazci ChainedDriver – nulový disk I/O.
 * V PHP-FPM workeri prežije medzi požiadavkami, čo znižuje latenciu
 * opakovaných čítaní (zoznamy stránok, parsed markdown).
 *
 * TTL je voliteľný; expirované kľúče sa odstraňujú pri get()/has().
 */
class MemoryDriver implements DriverInterface
{
    /** @var array<string, array{value: mixed, expires: ?int}> */
    private array $store = [];

    public function get(string $key, $default = null)
    {
        if (!$this->isValid($key)) {
            unset($this->store[$key]);

            return $default;
        }

        return $this->store[$key]['value'];
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $this->store[$key] = [
            'value' => $value,
            'expires' => $ttl !== null ? time() + $ttl : null,
        ];

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->store = [];

        return true;
    }

    public function has(string $key): bool
    {
        return $this->isValid($key);
    }

    /**
     * Atomický increment v pamäti (vhodné pre rate limiting v rámci workeru).
     */
    public function increment(string $key, int $step = 1, ?int $ttl = null): int
    {
        $current = $this->isValid($key) ? (int) $this->store[$key]['value'] : 0;
        $new = $current + $step;
        $this->set($key, $new, $ttl);

        return $new;
    }

    private function isValid(string $key): bool
    {
        if (!isset($this->store[$key])) {
            return false;
        }

        $expires = $this->store[$key]['expires'];
        if ($expires !== null && $expires < time()) {
            return false;
        }

        return true;
    }
}
