<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Logging\Services\DebugEventLogger;
use PaginiumCMS\Core\Performance\PerformanceContext;
use PaginiumCMS\Core\Performance\PerformanceGuardSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds W3C Server-Timing header for request phase breakdown (Iteration 85d).
 */
final class ServerTimingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private PerformanceGuardSettings $settings,
        private PerformanceContext $context
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->shouldEmit()) {
            return $handler->handle($request);
        }

        $started = hrtime(true);

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            throw $e;
        }

        return $this->withTimingHeader($response, $started);
    }

    private function shouldEmit(): bool
    {
        if ($this->settings->serverTimingEnabled()) {
            return true;
        }

        return DebugEventLogger::isEnabled();
    }

    private function withTimingHeader(ResponseInterface $response, int $startedNs): ResponseInterface
    {
        $totalMs = (hrtime(true) - $startedNs) / 1_000_000;
        $storageMs = $this->context->storageMs();
        $sessionLockMs = $this->context->sessionLockMs();
        $appMs = max(0.0, round($totalMs - $storageMs - $sessionLockMs, 2));

        $header = sprintf(
            'sess-lock;dur=%s, storage;dur=%s, app;dur=%s',
            $this->formatMs($sessionLockMs),
            $this->formatMs($storageMs),
            $this->formatMs($appMs)
        );

        return $response->withHeader('Server-Timing', $header);
    }

    private function formatMs(float $value): string
    {
        // HTTP header must use ASCII dot — locale must not affect Server-Timing (sk_SK → comma).
        return number_format($value, 2, '.', '');
    }
}
