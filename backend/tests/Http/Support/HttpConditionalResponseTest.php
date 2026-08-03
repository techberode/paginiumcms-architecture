<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Support;

use PaginiumCMS\Http\Support\HttpConditionalResponse;
use PaginiumCMS\Tests\Http\TestCase;

final class HttpConditionalResponseTest extends TestCase
{
    public function testPublicSettingsEndpointSupportsEtag(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/settings/public')
        );
        $etag = $response->getHeaderLine('ETag');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame('', $etag);

        $conditional = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/settings/public')->withHeader('If-None-Match', $etag)
        );

        $this->assertSame(304, $conditional->getStatusCode());
        $this->assertSame('', (string) $conditional->getBody());
    }

    public function testPublicPagesListSupportsEtag(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/pages')
        );
        $etag = $response->getHeaderLine('ETag');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame('', $etag);
        $this->assertStringContainsString('max-age=', $response->getHeaderLine('Cache-Control'));

        $conditional = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/pages')->withHeader('If-None-Match', $etag)
        );

        $this->assertSame(304, $conditional->getStatusCode());
        $this->assertSame('', (string) $conditional->getBody());
    }

    public function testPublicArticlesListSupportsEtag(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/articles')
        );
        $etag = $response->getHeaderLine('ETag');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame('', $etag);

        $conditional = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/articles')->withHeader('If-None-Match', $etag)
        );

        $this->assertSame(304, $conditional->getStatusCode());
    }

    public function testWeakEtagIsStableForSameBody(): void
    {
        $body = '{"success":true}';
        $this->assertSame(
            HttpConditionalResponse::weakEtag($body),
            HttpConditionalResponse::weakEtag($body)
        );
    }
}
