<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Resolves stable route templates without raw slugs, tokens, or IDs (Iteration 71).
 */
final class PerformanceRouteLabelResolver
{
    public function resolve(ServerRequestInterface $request): string
    {
        try {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            if ($route !== null) {
                $pattern = $route->getPattern();

                return $pattern !== '' ? $pattern : '/';
            }
        } catch (\Throwable) {
            // Route context unavailable (e.g. unit test or pre-routing) — sanitize path below.
        }

        return $this->sanitizePath($request->getUri()->getPath());
    }

    private function sanitizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            return '/';
        }

        $path = preg_replace(
            '#/[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}#i',
            '/{id}',
            $path
        ) ?? $path;

        $path = preg_replace('#/\d+#', '/{id}', $path) ?? $path;

        $segments = explode('/', trim($path, '/'));
        $sanitized = [];
        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }

            if ($this->looksLikeToken($segment)) {
                $sanitized[] = '{token}';
                continue;
            }

            if ($index > 0 && $this->looksLikeSlug($segment)) {
                $sanitized[] = '{slug}';
                continue;
            }

            $sanitized[] = $segment;
        }

        return '/' . implode('/', $sanitized);
    }

    private function looksLikeToken(string $segment): bool
    {
        return strlen($segment) >= 24 && (bool) preg_match('/^[A-Za-z0-9_-]+$/', $segment);
    }

    private function looksLikeSlug(string $segment): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)+$/i', $segment);
    }
}
