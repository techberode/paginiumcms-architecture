<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Cache\CacheManager;

/**
 * Rate limiter for POST /api/comments (It.80f — API4).
 */
final class CommentSubmitRateLimitMiddleware extends RateLimitMiddleware
{
    /**
     * @param array<int|string, mixed> $trustedProxies
     */
    public function __construct(CacheManager $cache, array $trustedProxies = [])
    {
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        $isTesting = $appEnv === 'testing';
        $isDevelopment = $appEnv === 'development' || $appEnv === 'local';

        parent::__construct(
            $cache,
            maxRequests: $isTesting ? 100000 : ($isDevelopment ? 60 : 15),
            window: $isTesting ? 60 : 3600,
            excludedPaths: [],
            excludedIps: $isTesting ? ['127.0.0.1', '::1'] : [],
            trustedProxies: $trustedProxies
        );
    }
}
