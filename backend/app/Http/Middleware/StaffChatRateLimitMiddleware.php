<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Http\Support\RequestJsonBody;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Caps public staff-card chat posts into Messages (It.93o-3).
 */
final class StaffChatRateLimitMiddleware extends RateLimitMiddleware
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
            maxRequests: $isTesting ? 100000 : ($isDevelopment ? 30 : 8),
            window: $isTesting ? 60 : 3600,
            excludedPaths: [],
            excludedIps: $isTesting ? ['127.0.0.1', '::1'] : [],
            trustedProxies: $trustedProxies
        );
    }

    protected function getCacheKey(ServerRequestInterface $request): string
    {
        $data = RequestJsonBody::decode($request) ?? [];
        $email = strtolower(trim((string) ($data['email'] ?? 'unknown')));

        return sprintf('rate_limit_staff_chat:%s:%s', md5($email), md5($this->getClientIp($request)));
    }
}
