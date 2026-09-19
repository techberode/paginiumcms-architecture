<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Cache\CacheManager;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Caps outbound HEAD probes from POST /api/auth/me/social/verify (It.93o-2).
 */
final class SocialVerifyRateLimitMiddleware extends RateLimitMiddleware
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
            maxRequests: $isTesting ? 100000 : ($isDevelopment ? 40 : 20),
            window: 60,
            excludedPaths: [],
            excludedIps: $isTesting ? ['127.0.0.1', '::1'] : [],
            trustedProxies: $trustedProxies
        );
    }

    protected function getCacheKey(ServerRequestInterface $request): string
    {
        return sprintf('rate_limit_social_verify:%s', md5($this->getClientIp($request)));
    }
}
