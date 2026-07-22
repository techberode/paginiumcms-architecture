<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Cache\CacheManager;

final class OtpStartRateLimitMiddleware extends OtpRateLimitMiddleware
{
    /**
     * @param array<int|string, mixed> $trustedProxies
     */
    public function __construct(CacheManager $cache, array $trustedProxies = [])
    {
        parent::__construct($cache, self::ACTION_START, $trustedProxies);
    }
}
