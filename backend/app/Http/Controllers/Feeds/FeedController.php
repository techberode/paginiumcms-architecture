<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Feeds;

use PaginiumCMS\Core\Feeds\Services\FeedGenerator;
use PaginiumCMS\Core\Feeds\Services\SitemapGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Public RSS and sitemap endpoints (Iteration 22).
 */
final class FeedController
{
    public function __construct(
        private FeedGenerator $feedGenerator,
        private SitemapGenerator $sitemapGenerator
    ) {
    }

    public function rss(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $xml = $this->feedGenerator->generate();

        $response->getBody()->write($xml);

        return $response
            ->withHeader('Content-Type', 'application/rss+xml; charset=utf-8')
            ->withHeader('Cache-Control', 'public, max-age=300');
    }

    public function sitemap(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $xml = $this->sitemapGenerator->generate();

        $response->getBody()->write($xml);

        return $response
            ->withHeader('Content-Type', 'application/xml; charset=utf-8')
            ->withHeader('Cache-Control', 'public, max-age=300');
    }
}
