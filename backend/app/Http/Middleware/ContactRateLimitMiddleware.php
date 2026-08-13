<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Support\JsonHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Rate limiter for POST /api/contact (It.80f — API4).
 *
 * Limits:
 * - 5 requests / hour per client IP
 * - 3 requests / day per normalized email address
 */
final class ContactRateLimitMiddleware extends RateLimitMiddleware
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
        if ((getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '')) === 'testing') {
            return $handler->handle($request);
        }

        $ipKey = sprintf('rate_limit_contact_ip:%s', md5($this->getClientIp($request)));
        $ipCount = (int) $this->cacheManager->get($ipKey, 0);
        if ($ipCount >= $this->maxRequestsForParent()) {
            return $this->rateLimitResponse();
        }

        $body = RequestJsonBody::decode($request);
        if (is_array($body)) {
            $email = strtolower(trim((string) ($body['email'] ?? '')));
            if ($email !== '') {
                $emailKey = sprintf('rate_limit_contact_email:%s', md5($email));
                $emailCount = (int) $this->cacheManager->get($emailKey, 0);
                if ($emailCount >= $this->emailMaxRequests) {
                    return $this->rateLimitResponse();
                }
                $this->cacheManager->set($emailKey, $emailCount + 1, $this->emailWindow);
            }
        }

        $this->cacheManager->set($ipKey, $ipCount + 1, $this->windowForParent());

        if ($request->getBody()->isSeekable()) {
            $request->getBody()->rewind();
        }

        return $handler->handle($request);
    }

    private function maxRequestsForParent(): int
    {
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        $isTesting = $appEnv === 'testing';
        $isDevelopment = $appEnv === 'development' || $appEnv === 'local';

        return $isTesting ? 100000 : ($isDevelopment ? 30 : 5);
    }

    private function windowForParent(): int
    {
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');

        return $appEnv === 'testing' ? 60 : 3600;
    }

    private function rateLimitResponse(): ResponseInterface
    {
        $response = new Response(429);
        $response->getBody()->write(JsonHelper::encode([
            'success' => false,
            'error' => 'Too many contact requests. Please try again later.',
            'code' => 'rate_limit_exceeded',
        ]));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
