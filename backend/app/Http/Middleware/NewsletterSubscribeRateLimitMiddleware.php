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
 * Dedicated rate limiter for public newsletter subscribe endpoints (audit A7).
 *
 * Limits:
 * - 5 requests / hour per client IP
 * - 3 requests / day per normalized email address
 */
final class NewsletterSubscribeRateLimitMiddleware extends RateLimitMiddleware
{
    private CacheManager $cacheManager;
    private int $emailMaxRequests;
    private int $emailWindow;

    /**
     * @param array<int|string, mixed> $trustedProxies
     */
    public function __construct(CacheManager $cache, array $trustedProxies = [])
    {
        $this->cacheManager = $cache;

        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        $isTesting = $appEnv === 'testing';
        $isDevelopment = $appEnv === 'development' || $appEnv === 'local';

        $this->emailMaxRequests = $isTesting ? 100000 : ($isDevelopment ? 30 : 3);
        $this->emailWindow = $isTesting ? 60 : 86400;

        parent::__construct(
            $cache,
            maxRequests: $isTesting ? 100000 : ($isDevelopment ? 30 : 5),
            window: $isTesting ? 60 : 3600,
            excludedPaths: [],
            excludedIps: $isTesting ? ['127.0.0.1', '::1'] : [],
            trustedProxies: $trustedProxies
        );
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if ($this->isPublicTestingEnvironment()) {
            return $handler->handle($request);
        }

        $rawBody = (string) $request->getBody();
        if ($request->getBody()->isSeekable()) {
            $request->getBody()->rewind();
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($rawBody, true) ?: [];
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $ipKey = sprintf('rate_limit_newsletter:ip:%s', md5($this->resolveClientIp($request)));

        if (!$this->incrementWithinLimit($ipKey, $this->getIpMaxRequests(), $this->getIpWindow())) {
            return $this->buildRateLimitResponse($this->getIpWindow());
        }

        if ($email !== '') {
            $emailKey = sprintf('rate_limit_newsletter:email:%s', md5($email));
            if (!$this->incrementWithinLimit($emailKey, $this->emailMaxRequests, $this->emailWindow)) {
                return $this->buildRateLimitResponse($this->emailWindow);
            }
        }

        return $handler->handle($request);
    }

    private function isPublicTestingEnvironment(): bool
    {
        return (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '')) === 'testing';
    }

    private function resolveClientIp(ServerRequestInterface $request): string
    {
        return $this->getClientIp($request);
    }

    private function getIpMaxRequests(): int
    {
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        if ($appEnv === 'testing') {
            return 100000;
        }
        if ($appEnv === 'development' || $appEnv === 'local') {
            return 30;
        }

        return 5;
    }

    private function getIpWindow(): int
    {
        return (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '')) === 'testing' ? 60 : 3600;
    }

    private function incrementWithinLimit(string $key, int $maxRequests, int $window): bool
    {
        $current = (int) $this->cacheManager->get($key, 0);
        if ($current >= $maxRequests) {
            return false;
        }

        $this->cacheManager->set($key, $current + 1, $window);

        return true;
    }

    private function buildRateLimitResponse(int $window): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(JsonHelper::encode([
            'success' => false,
            'error' => 'Too many subscription attempts. Please try again later.',
            'retry_after' => $window,
        ], JSON_PRETTY_PRINT));

        return $response
            ->withStatus(429)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Retry-After', (string) $window);
    }
}
