<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Drivers;

/**
 * Dvojvrstvý cache driver: Memory → File.
 *
 * Optimalizácia pre flat-file CMS:
 * 1. Prvá požiadavka na workeri: miss v RAM, hit/miss na disku.
 * 2. Ďalšie požiadavky v tom istom workeri: hit v RAM bez disk I/O.
 *
 * Zápis ide do oboch vrstiev (write-through), aby invalidácia bola konzistentná.
 */
class ChainedDriver implements DriverInterface
{
    public function __construct(
        private MemoryDriver $memory,
        private FileDriver $file
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->memory->has($key)) {
            return $this->memory->get($key, $default);
        }

        $value = $this->file->get($key, $default);
        if ($value !== $default) {
            // Propagácia do RAM bez TTL (file drží expiráciu)
            $this->memory->set($key, $value);
        }

        return $value;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->memory->set($key, $value, $ttl);

        return $this->file->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        $this->memory->delete($key);

        return $this->file->delete($key);
    }

    public function clear(): bool
    {
        $this->memory->clear();

        return $this->file->clear();
    }

    public function has(string $key): bool
    {
        return $this->memory->has($key) || $this->file->has($key);
    }

    public function increment(string $key, int $step = 1, ?int $ttl = null): int
    {
        // File je autoritatívny zdroj – RAM môže mať zastaranú generáciu.
        $new = $this->file->increment($key, $step, $ttl);
        $this->memory->set($key, $new, $ttl);

        return $new;
    }
}
