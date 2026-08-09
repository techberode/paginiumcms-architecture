<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Seo\Services\RedirectStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Applies flat-file redirect rules before route dispatch (It.80a).
 */
final class RedirectMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const SKIP_PREFIXES = [
        '/api/',
        '/storage/',
    ];

    public function __construct(
        private RedirectStore $redirects,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!in_array(strtoupper($request->getMethod()), ['GET', 'HEAD'], true)) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();
        if ($path === '/favicon.ico') {
            return $handler->handle($request);
        }

        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $handler->handle($request);
            }
        }

        try {
            $match = $this->redirects->match($path);
        } catch (\InvalidArgumentException) {
            return $handler->handle($request);
        }

        if ($match === null) {
            return $handler->handle($request);
        }

        $location = $match['to'];
        $query = $request->getUri()->getQuery();
        if ($query !== '') {
            $location .= (str_contains($location, '?') ? '&' : '?') . $query;
        }

        $response = new Response($match['status']);
        $response = $response->withHeader('Location', $location);
        if ($request->getMethod() === 'HEAD') {
            return $response->withHeader('Content-Length', '0');
        }

        return $response;
    }
}
