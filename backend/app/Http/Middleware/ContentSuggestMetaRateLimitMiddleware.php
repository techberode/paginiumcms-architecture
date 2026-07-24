<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Core\Cache\CacheManager;
use Psr\Http\Message\ServerRequestInterface;

/** Rate limit for POST /api/admin/content/suggest-meta (It.57). */
final class ContentSuggestMetaRateLimitMiddleware extends RateLimitMiddleware
{
    /**
     * @param array<int|string, mixed> $trustedProxies
     */
    public function __construct(CacheManager $cache, array $trustedProxies = [])
    {
        $isTesting = (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '')) === 'testing';

        parent::__construct(
            $cache,
            maxRequests: $isTesting ? 100000 : 30,
            window: 60,
            excludedPaths: [],
            excludedIps: $isTesting ? ['127.0.0.1', '::1'] : [],
            trustedProxies: $trustedProxies,
        );
    }

    protected function getCacheKey(ServerRequestInterface $request): string
    {
        $user = $request->getAttribute('user');
        $userId = $user instanceof User ? $user->getId() : 'anon';
        $ip = $this->getClientIp($request);

        return sprintf('rate_limit_content_suggest_meta:%s:%s', md5($userId), md5($ip));
    }
}
