<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Logging\Services\AccessLogService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Production HTTP access logging — timestamp + IP on every entry (settings-driven).
 */
final class RequestLoggingMiddleware implements MiddlewareInterface
{
    /** @var array<int|string, mixed> */
    private array $trustedProxies;

    /**
     * @param array<int|string, mixed> $trustedProxies
     */
    public function __construct(
        private AccessLogService $accessLog,
        array $trustedProxies = []
    ) {
        $this->trustedProxies = $trustedProxies;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isTestingEnvironment() || !$this->accessLog->isEnabled()) {
            return $handler->handle($request);
        }

        $started = microtime(true);
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();
        $ip = $this->resolveClientIp($request);

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            $this->accessLog->logRequest(
                $ip,
                $method,
                $path,
                500,
                $this->durationMs($started),
                $this->userIdFromRequest($request),
                [
                    'exception' => $e::class,
                    'error' => $e->getMessage(),
                ]
            );
            throw $e;
        }

        $this->accessLog->logRequest(
            $ip,
            $method,
            $path,
            $response->getStatusCode(),
            $this->durationMs($started),
            $this->userIdFromRequest($request),
            [
                'query' => $request->getUri()->getQuery(),
                'user_agent' => mb_substr($request->getHeaderLine('User-Agent'), 0, 256),
            ]
        );

        return $response;
    }

    private function durationMs(float $started): float
    {
        return round((microtime(true) - $started) * 1000, 2);
    }

    private function resolveClientIp(ServerRequestInterface $request): string
    {
        $remoteAddr = $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
        $remoteAddr = is_string($remoteAddr) ? $remoteAddr : '127.0.0.1';

        if ($this->trustedProxies === [] || !in_array($remoteAddr, $this->trustedProxies, true)) {
            return $remoteAddr;
        }

        $forwarded = $request->getHeaderLine('X-Forwarded-For');
        if ($forwarded === '') {
            return $remoteAddr;
        }

        $parts = array_map('trim', explode(',', $forwarded));
        $clientIp = $parts[0];

        return filter_var($clientIp, FILTER_VALIDATE_IP) ? $clientIp : $remoteAddr;
    }

    private function userIdFromRequest(ServerRequestInterface $request): ?string
    {
        $user = $request->getAttribute('user');
        if (is_object($user) && method_exists($user, 'getId')) {
            return (string) $user->getId();
        }

        return null;
    }

    private function isTestingEnvironment(): bool
    {
        return getenv('APP_ENV') === 'testing' || ($_ENV['APP_ENV'] ?? '') === 'testing';
    }
}
