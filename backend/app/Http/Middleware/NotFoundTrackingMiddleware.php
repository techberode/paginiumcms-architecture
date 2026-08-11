<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Seo\Services\NotFoundHitStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Records aggregated 404 hits for public-facing paths (It.80b).
 */
final class NotFoundTrackingMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const SKIP_PREFIXES = [
        '/api/admin/',
        '/api/auth/',
        '/api/health',
        '/api/debug/',
        '/storage/',
    ];

    public function __construct(
        private NotFoundHitStore $store,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        if (!$this->shouldTrack($request)) {
            return $response;
        }

        try {
            $this->store->record(
                $request->getUri()->getPath(),
                $request->getHeaderLine('Referer') ?: null,
                $request->getHeaderLine('User-Agent') ?: null
            );
        } catch (\Throwable) {
            // Metrics must never break responses.
        }

        return $response;
    }

    private function shouldTrack(ServerRequestInterface $request): bool
    {
        if (!in_array(strtoupper($request->getMethod()), ['GET', 'HEAD'], true)) {
            return false;
        }

        $path = $request->getUri()->getPath();
        if ($path === '/favicon.ico') {
            return false;
        }

        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        return true;
    }
}
