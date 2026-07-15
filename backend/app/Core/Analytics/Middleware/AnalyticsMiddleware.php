<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Middleware;

use PaginiumCMS\Core\Analytics\Services\AnalyticsManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Tracks public page views (Iteration 6). Skips API and admin paths.
 */
final class AnalyticsMiddleware implements MiddlewareInterface
{
    public function __construct(private AnalyticsManager $analytics)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if ($this->shouldTrack($path)) {
            $this->analytics->trackPageView($path, $request->getHeaderLine('Referer') ?: null);
        }

        return $handler->handle($request);
    }

    private function shouldTrack(string $path): bool
    {
        if (str_starts_with($path, '/api/')) {
            return false;
        }

        $skip = ['/favicon.ico', '/assets/', '/health'];
        foreach ($skip as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        return true;
    }
}
