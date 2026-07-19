<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Feeds;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Feeds\Services\FeedGenerator;
use PaginiumCMS\Core\Feeds\Services\RobotsTxtGenerator;
use PaginiumCMS\Core\Feeds\Services\SitemapGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Public RSS, sitemap, and robots endpoints (Iteration 22 + It.10 polish).
 */
final class FeedController
{
    public function __construct(
        private FeedGenerator $feedGenerator,
        private SitemapGenerator $sitemapGenerator,
        private RobotsTxtGenerator $robotsTxtGenerator,
        private ContentCacheService $contentCache
    ) {
    }

    public function rss(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $xml = $this->contentCache->rememberFeedRss(
            fn (): string => $this->feedGenerator->generate()
        );

        $response->getBody()->write($xml);

        return $response
            ->withHeader('Content-Type', 'application/rss+xml; charset=utf-8')
            ->withHeader('Cache-Control', 'public, max-age=300');
    }

    public function sitemap(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $xml = $this->contentCache->rememberFeedSitemap(
            fn (): string => $this->sitemapGenerator->generate()
        );

        $response->getBody()->write($xml);

        return $response
            ->withHeader('Content-Type', 'application/xml; charset=utf-8')
            ->withHeader('Cache-Control', 'public, max-age=300');
    }

    public function robots(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->contentCache->rememberFeedRobots(
            fn (): string => $this->robotsTxtGenerator->generate()
        );

        $response->getBody()->write($body);

        return $response
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withHeader('Cache-Control', 'public, max-age=300');
    }
}
