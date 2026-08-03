<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Contracts;

use PaginiumCMS\Core\Cache\Drivers\DriverInterface;

/**
 * Hybrid Engine cache driver contract (Iteration 69).
 *
 * Extends the legacy driver with health diagnostics and tag invalidation hooks.
 */
interface CacheDriverInterface extends DriverInterface
{
    /**
     * @return array{ok: bool, driver: string, latencyMs: int, message?: string}
     */
    public function health(): array;

    /**
     * @param list<string> $tags
     */
    public function invalidateTags(array $tags): int;

    /**
     * Associates a stored cache key with one or more invalidation tags.
     *
     * @param list<string> $tags
     */
    public function tagKey(string $key, array $tags): void;
}
