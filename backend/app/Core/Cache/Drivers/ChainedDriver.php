<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Drivers;

use PaginiumCMS\Core\Cache\Contracts\CacheDriverInterface;

/**
 * Dvojvrstvý cache driver: Memory → File.
 *
 * Optimalizácia pre flat-file CMS:
 * 1. Prvá požiadavka na workeri: miss v RAM, hit/miss na disku.
 * 2. Ďalšie požiadavky v tom istom workeri: hit v RAM bez disk I/O.
 *
 * Zápis ide do oboch vrstiev (write-through), aby invalidácia bola konzistentná.
 */
class ChainedDriver implements CacheDriverInterface
{
    public function __construct(
        private MemoryDriver $memory,
        private CacheDriverInterface $persistent,
        private string $driverLabel = 'auto',
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->memory->has($key)) {
            return $this->memory->get($key, $default);
        }

        $value = $this->persistent->get($key, $default);
        if ($value !== $default) {
            // Propagácia do RAM bez TTL (file drží expiráciu)
            $this->memory->set($key, $value);
        }

        return $value;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->memory->set($key, $value, $ttl);

        return $this->persistent->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        $this->memory->delete($key);

        return $this->persistent->delete($key);
    }

    public function clear(): bool
    {
        $this->memory->clear();

        return $this->persistent->clear();
    }

    public function has(string $key): bool
    {
        return $this->memory->has($key) || $this->persistent->has($key);
    }

    public function increment(string $key, int $step = 1, ?int $ttl = null): int
    {
        // File je autoritatívny zdroj – RAM môže mať zastaranú generáciu.
        if (method_exists($this->persistent, 'increment')) {
            $new = $this->persistent->increment($key, $step, $ttl);
        } else {
            $current = (int) $this->persistent->get($key, 0);
            $new = $current + $step;
            $this->persistent->set($key, $new, $ttl);
        }
        $this->memory->set($key, $new, $ttl);

        return $new;
    }

    public function health(): array
    {
        $memory = $this->memory->health();
        $persistent = $this->persistent->health();

        return [
            'ok' => $memory['ok'] && $persistent['ok'],
            'driver' => $this->driverLabel,
            'latencyMs' => $memory['latencyMs'] + $persistent['latencyMs'],
            'message' => 'Chained memory + ' . $persistent['driver'] . ' cache.',
        ];
    }

    /**
     * @param list<string> $tags
     */
    public function invalidateTags(array $tags): int
    {
        return $this->memory->invalidateTags($tags) + $this->persistent->invalidateTags($tags);
    }

    /**
     * @param list<string> $tags
     */
    public function tagKey(string $key, array $tags): void
    {
        $this->memory->tagKey($key, $tags);
        $this->persistent->tagKey($key, $tags);
    }
}
