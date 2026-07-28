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
 * Rate limit for newsletter confirm/unsubscribe token endpoints (IP only).
 */
final class NewsletterTokenRateLimitMiddleware extends RateLimitMiddleware
{
    private CacheManager $cacheManager;
    private int $ipMaxRequests;
    private int $ipWindow;

    /**
     * @param array<int|string, mixed> $trustedProxies
     */
    public function __construct(CacheManager $cache, array $trustedProxies = [])
    {
        $this->cacheManager = $cache;

        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        $isTesting = $appEnv === 'testing';
        $isDevelopment = $appEnv === 'development' || $appEnv === 'local';

        $this->ipMaxRequests = $isTesting ? 100000 : ($isDevelopment ? 100 : 30);
        $this->ipWindow = $isTesting ? 60 : 3600;

        parent::__construct(
            $cache,
            maxRequests: $this->ipMaxRequests,
            window: $this->ipWindow,
            excludedPaths: [],
            excludedIps: $isTesting ? ['127.0.0.1', '::1'] : [],
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

        $ipKey = sprintf('rate_limit_newsletter_token:ip:%s', md5($this->getClientIp($request)));
        $current = (int) $this->cacheManager->get($ipKey, 0);
        if ($current >= $this->ipMaxRequests) {
            $response = new Response();
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => $this->ipWindow,
            ], JSON_PRETTY_PRINT));

            return $response
                ->withStatus(429)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string) $this->ipWindow);
        }

        $this->cacheManager->set($ipKey, $current + 1, $this->ipWindow);

        return $handler->handle($request);
    }
}
