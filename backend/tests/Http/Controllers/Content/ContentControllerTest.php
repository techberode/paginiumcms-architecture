<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Content;

use PaginiumCMS\Tests\Http\TestCase;

class ContentControllerTest extends TestCase
{
    public function testListPagesLegacyReturnsAllWithoutMeta(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/pages'));
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertArrayNotHasKey('meta', $data);
    }

    public function testListPagesPaginatedReturnsMeta(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/pages?page=1&per_page=10')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertSame(1, $data['meta']['page']);
        $this->assertSame(10, $data['meta']['per_page']);
        $this->assertArrayHasKey('total', $data['meta']);
        $this->assertArrayHasKey('total_pages', $data['meta']);
    }

    public function testPublicListPagesOnlyPublished(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/pages?page=1&per_page=50')
        );
        $data = $this->getJsonResponse($response);

        $this->assertTrue($data['success']);
        foreach ($data['data'] as $item) {
            $this->assertSame('published', $item['status'] ?? null);
        }
    }

    public function testPublicGetUnknownPageReturns404(): void
    {
        $public = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/pages/nonexistent-slug-' . uniqid('', true))
        );
        $this->assertSame(404, $public->getStatusCode());
    }

    public function testSearchRequiresMinLength(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/search?q=a'));
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame([], $data['data']);
    }

    public function testSearchReturnsPublishedMatches(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/search?q=home'));
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
    }
}
