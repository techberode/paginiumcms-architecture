<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Support\JsonHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Rate limiter for public analytics pageview beacons.
 */
final class AnalyticsPageviewRateLimitMiddleware extends RateLimitMiddleware
{
    private CacheManager $cacheManager;
    private int $pageviewMaxRequests;
    private int $pageviewWindow;

    /**
     * @param array<int|string, mixed> $trustedProxies
     */
    public function __construct(CacheManager $cache, array $trustedProxies = [])
    {
        $this->cacheManager = $cache;

        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        $isTesting = $appEnv === 'testing';
        $isDevelopment = $appEnv === 'development' || $appEnv === 'local';

        $this->pageviewMaxRequests = $isTesting ? 100000 : ($isDevelopment ? 500 : 180);
        $this->pageviewWindow = $isTesting ? 60 : 3600;

        parent::__construct(
            $cache,
            maxRequests: $this->pageviewMaxRequests,
            window: $this->pageviewWindow,
            excludedPaths: [],
            excludedIps: [],
            trustedProxies: $trustedProxies
        );
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if ((getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '')) === 'testing') {
            return $handler->handle($request);
        }

        $key = sprintf('rate_limit_analytics_pageview:%s', md5($this->getClientIp($request)));
        $current = (int) $this->cacheManager->get($key, 0);
        if ($current >= $this->pageviewMaxRequests) {
            $response = new Response();
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => 'Too many analytics requests',
                'retry_after' => $this->pageviewWindow,
            ]));

            return $response
                ->withStatus(429)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string) $this->pageviewWindow);
        }

        $this->cacheManager->set($key, $current + 1, $this->pageviewWindow);

        return $handler->handle($request);
    }
}
