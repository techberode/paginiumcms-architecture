<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\PublicApi;

use PaginiumCMS\Core\StaticSite\StaticHtmlServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Public GET for compiled HTML (It.48b). Anonymous. 404 in dynamic mode or when missing.
 */
final class StaticHtmlController
{
    public function __construct(
        private StaticHtmlServer $server,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function page(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->respond($response, 'page', (string) ($args['slug'] ?? ''));
    }

    /**
     * @param array<string, string> $args
     */
    public function article(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->respond($response, 'article', (string) ($args['slug'] ?? ''));
    }

    private function respond(ResponseInterface $response, string $type, string $slug): ResponseInterface
    {
        $loaded = $this->server->load($type, $slug);
        if ($loaded === null) {
            return $response
                ->withStatus(404)
                ->withHeader('Cache-Control', 'no-store')
                ->withHeader('X-Content-Type-Options', 'nosniff');
        }

        $response->getBody()->write($loaded['html']);

        return $response
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Content-Security-Policy', StaticHtmlServer::CONTENT_SECURITY_POLICY)
            ->withHeader('Cache-Control', 'public, max-age=60');
    }
}
