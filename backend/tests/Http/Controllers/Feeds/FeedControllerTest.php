<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Feeds;

use PaginiumCMS\Tests\Http\TestCase;

final class FeedControllerTest extends TestCase
{
    public function testFeedAndSitemapRoutesReturnXml(): void
    {
        $feedRequest = $this->createJsonRequest('GET', '/feed.xml');
        $feedResponse = $this->handleRequest($feedRequest);

        $this->assertEquals(200, $feedResponse->getStatusCode());
        $this->assertStringContainsString('application/rss+xml', $feedResponse->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('<rss', (string) $feedResponse->getBody());

        $sitemapRequest = $this->createJsonRequest('GET', '/sitemap.xml');
        $sitemapResponse = $this->handleRequest($sitemapRequest);

        $this->assertEquals(200, $sitemapResponse->getStatusCode());
        $this->assertStringContainsString('application/xml', $sitemapResponse->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('<urlset', (string) $sitemapResponse->getBody());
    }

    public function testRobotsRouteReturnsPlainTextWithSitemap(): void
    {
        $request = $this->createJsonRequest('GET', '/robots.txt');
        $response = $this->handleRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/plain', $response->getHeaderLine('Content-Type'));
        $body = (string) $response->getBody();
        $this->assertStringContainsString('User-agent: *', $body);
        $this->assertStringContainsString('Sitemap:', $body);
    }
}
