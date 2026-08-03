<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Drivers;

use PaginiumCMS\Core\Cache\Contracts\CacheDriverInterface;

/**
 * In-process cache driver (per PHP worker).
 *
 * Najrýchlejšia vrstva v reťazci ChainedDriver – nulový disk I/O.
 * V PHP-FPM workeri prežije medzi požiadavkami, čo znižuje latenciu
 * opakovaných čítaní (zoznamy stránok, parsed markdown).
 *
 * TTL je voliteľný; expirované kľúče sa odstraňujú pri get()/has().
 */
class MemoryDriver implements CacheDriverInterface
{
    /** @var array<string, array{value: mixed, expires: ?int}> */
    private array $store = [];

    /** @var array<string, list<string>> */
    private array $tagIndex = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isValid($key)) {
            unset($this->store[$key]);

            return $default;
        }

        return $this->store[$key]['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
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
        $this->tagIndex = [];

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

    public function health(): array
    {
        $started = hrtime(true);
        $probeKey = '__health_' . bin2hex(random_bytes(4));
        $ok = $this->set($probeKey, 'ok', 5)
            && $this->get($probeKey) === 'ok'
            && $this->delete($probeKey);

        return [
            'ok' => $ok,
            'driver' => 'memory',
            'latencyMs' => (int) ((hrtime(true) - $started) / 1_000_000),
            'message' => $ok ? 'In-process memory cache operational.' : 'Memory cache health probe failed.',
        ];
    }

    /**
     * @param list<string> $tags
     */
    public function invalidateTags(array $tags): int
    {
        $deleted = 0;

        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag === '' || !isset($this->tagIndex[$tag])) {
                continue;
            }

            foreach ($this->tagIndex[$tag] as $key) {
                if ($this->delete($key)) {
                    ++$deleted;
                }
            }

            unset($this->tagIndex[$tag]);
        }

        return $deleted;
    }

    /**
     * @param list<string> $tags
     */
    public function tagKey(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag === '') {
                continue;
            }

            if (!in_array($key, $this->tagIndex[$tag] ?? [], true)) {
                $this->tagIndex[$tag][] = $key;
            }
        }
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
