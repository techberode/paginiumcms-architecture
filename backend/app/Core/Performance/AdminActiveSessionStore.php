<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use PaginiumCMS\Core\Cache\CacheManager;

/**
 * It.100f — approximate count of concurrent authenticated admin sessions on this instance.
 */
final class AdminActiveSessionStore
{
    private const CACHE_KEY = 'admin.active_sessions.registry';

    public function __construct(private CacheManager $cache)
    {
    }

    public function touch(string $sessionId): void
    {
        if ($sessionId === '') {
            return;
        }

        $now = time();
        $window = $this->activityWindowSeconds();
        $registry = $this->readRegistry();
        $registry[$sessionId] = $now;

        foreach ($registry as $id => $seenAt) {
            if ($now - (int) $seenAt > $window) {
                unset($registry[$id]);
            }
        }

        $this->cache->set(self::CACHE_KEY, $registry, $window);
    }

    public function countActive(): int
    {
        $now = time();
        $window = $this->activityWindowSeconds();
        $registry = $this->readRegistry();
        $active = 0;

        foreach ($registry as $seenAt) {
            if ($now - (int) $seenAt <= $window) {
                ++$active;
            }
        }

        return $active;
    }

    /**
     * @return array<string, int>
     */
    private function readRegistry(): array
    {
        $raw = $this->cache->get(self::CACHE_KEY);
        if (!is_array($raw)) {
            return [];
        }

        $registry = [];
        foreach ($raw as $sessionId => $seenAt) {
            if (!is_string($sessionId)) {
                continue;
            }
            $registry[$sessionId] = (int) $seenAt;
        }

        return $registry;
    }

    private function activityWindowSeconds(): int
    {
        if (function_exists('paginium_session_lifetime_seconds')) {
            return max(300, paginium_session_lifetime_seconds());
        }

        return 7200;
    }
}
