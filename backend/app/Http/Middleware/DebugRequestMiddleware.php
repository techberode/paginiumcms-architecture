<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Logging\Services\DebugEventLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Loguje každý HTTP request/response pri APP_DEBUG=true.
 */
final class DebugRequestMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const SENSITIVE_PATHS = [
        '/api/auth/login',
        '/api/auth/register',
        '/api/auth/change-password',
        '/api/auth/verify-reset-token',
        '/api/auth/2fa/verify-login',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!DebugEventLogger::isEnabled()) {
            return $handler->handle($request);
        }

        $started = microtime(true);
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        DebugEventLogger::log('backend', 'http.request', [
            'method' => $method,
            'path' => $path,
            'query' => $request->getUri()->getQuery(),
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? null,
            'user_id' => $this->userIdFromRequest($request),
        ]);

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            DebugEventLogger::log('backend', 'http.exception', [
                'method' => $method,
                'path' => $path,
                'duration_ms' => $this->durationMs($started),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        DebugEventLogger::log('backend', 'http.response', [
            'method' => $method,
            'path' => $path,
            'status' => $response->getStatusCode(),
            'duration_ms' => $this->durationMs($started),
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'user_id' => $this->userIdFromRequest($request),
            'sensitive' => $this->isSensitivePath($path),
        ]);

        return $response;
    }

    private function durationMs(float $started): float
    {
        return round((microtime(true) - $started) * 1000, 2);
    }

    private function isSensitivePath(string $path): bool
    {
        foreach (self::SENSITIVE_PATHS as $sensitive) {
            if (str_starts_with($path, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    private function userIdFromRequest(ServerRequestInterface $request): ?string
    {
        $user = $request->getAttribute('user');
        if (is_object($user) && method_exists($user, 'getId')) {
            return (string) $user->getId();
        }

        return null;
    }
}
