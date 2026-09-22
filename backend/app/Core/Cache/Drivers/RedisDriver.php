<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Drivers;

use PaginiumCMS\Core\Cache\Contracts\CacheDriverInterface;
use PaginiumCMS\Core\Cache\RedisDriverConfig;
use PaginiumCMS\Support\JsonHelper;
use Redis;
use RedisException;

/**
 * Shared Redis cache layer (derived cache only — never SSOT).
 */
final class RedisDriver implements CacheDriverInterface
{
    private const TAG_INDEX_KEY = '__tag_index';

    private function __construct(
        private Redis $redis,
        private string $prefix,
    ) {
    }

    public static function connect(RedisDriverConfig $config): ?self
    {
        if (!extension_loaded('redis')) {
            return null;
        }

        try {
            $redis = new Redis();
            $connected = $redis->connect(
                $config->host,
                $config->port,
                $config->connectTimeoutSeconds
            );
            if ($connected !== true) {
                return null;
            }

            if ($config->password !== '') {
                if ($redis->auth($config->password) !== true) {
                    return null;
                }
            }

            if ($config->database > 0) {
                $redis->select($config->database);
            }

            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);

            return new self($redis, $config->keyPrefix);
        } catch (RedisException) {
            return null;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $raw = $this->redis->get($this->storageKey($key));
        if (!is_string($raw) || $raw === '') {
            return $default;
        }

        $decoded = JsonHelper::decode($raw);
        if (!array_key_exists('value', $decoded)) {
            return $default;
        }

        $expires = $decoded['expires'] ?? null;
        if ($expires !== null && (int) $expires < time()) {
            $this->delete($key);

            return $default;
        }

        return $decoded['value'] ?? $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $payload = JsonHelper::encode([
            'value' => $value,
            'expires' => $ttl !== null ? time() + $ttl : null,
            'created' => time(),
        ]);

        $storageKey = $this->storageKey($key);
        if ($ttl !== null && $ttl > 0) {
            return $this->redis->setex($storageKey, $ttl, $payload) === true;
        }

        return $this->redis->set($storageKey, $payload) === true;
    }

    public function delete(string $key): bool
    {
        return $this->redis->del($this->storageKey($key)) >= 0;
    }

    public function clear(): bool
    {
        $it = null;
        $pattern = $this->prefix . '*';

        do {
            /** @var array<int, string>|false $keys */
            $keys = $this->redis->scan($it, $pattern, 200);
            if ($keys === false) {
                break;
            }
            if ($keys !== []) {
                $this->redis->del($keys);
            }
        } while ($it !== 0 && $it !== false);

        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key, '__missing__') !== '__missing__';
    }

    /**
     * Atomic counter for rate limits and cache generations (same envelope as {@see set()}).
     */
    public function increment(string $key, int $step = 1, ?int $ttl = null): int
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $step;
        $this->set($key, $new, $ttl);

        return $new;
    }

    public function health(): array
    {
        $started = hrtime(true);
        try {
            if (!$this->pingOk($this->redis->ping())) {
                return [
                    'ok' => false,
                    'driver' => 'redis',
                    'latencyMs' => 0,
                    'message' => 'Redis PING failed.',
                ];
            }

            $probeKey = '__health_' . bin2hex(random_bytes(4));
            $ok = $this->set($probeKey, 'ok', 5)
                && $this->get($probeKey) === 'ok'
                && $this->delete($probeKey);

            return [
                'ok' => $ok,
                'driver' => 'redis',
                'latencyMs' => (int) ((hrtime(true) - $started) / 1_000_000),
                'message' => $ok ? 'Redis read/write/delete operational.' : 'Redis health probe failed.',
            ];
        } catch (RedisException $e) {
            return [
                'ok' => false,
                'driver' => 'redis',
                'latencyMs' => (int) ((hrtime(true) - $started) / 1_000_000),
                'message' => 'Redis error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @param list<string> $tags
     */
    public function invalidateTags(array $tags): int
    {
        if ($tags === []) {
            return 0;
        }

        $index = $this->loadTagIndex();
        $deleted = 0;

        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag === '' || !isset($index[$tag])) {
                continue;
            }

            foreach ($index[$tag] as $key) {
                if ($this->delete($key)) {
                    ++$deleted;
                }
            }

            unset($index[$tag]);
        }

        $this->saveTagIndex($index);

        return $deleted;
    }

    /**
     * @param list<string> $tags
     */
    public function tagKey(string $key, array $tags): void
    {
        if ($tags === []) {
            return;
        }

        $index = $this->loadTagIndex();

        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag === '') {
                continue;
            }

            if (!in_array($key, $index[$tag] ?? [], true)) {
                $index[$tag][] = $key;
            }
        }

        $this->saveTagIndex($index);
    }

    /**
     * @return array<string, list<string>>
     */
    private function loadTagIndex(): array
    {
        $raw = $this->redis->get($this->storageKey(self::TAG_INDEX_KEY));
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, list<string>> $index
     */
    private function saveTagIndex(array $index): void
    {
        $this->redis->set(
            $this->storageKey(self::TAG_INDEX_KEY),
            JsonHelper::encode($index)
        );
    }

    private function storageKey(string $key): string
    {
        return $this->prefix . hash('sha256', $key);
    }

    private function pingOk(mixed $pong): bool
    {
        if ($pong === true) {
            return true;
        }

        if (!is_string($pong)) {
            return false;
        }

        $normalized = strtoupper(trim($pong));

        return $normalized === 'PONG' || $normalized === '+PONG';
    }
}
