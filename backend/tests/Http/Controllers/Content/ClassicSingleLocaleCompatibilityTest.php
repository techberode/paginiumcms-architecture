<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Content;

use PaginiumCMS\Tests\Http\TestCase;

/**
 * Ensures legacy single-locale content remains readable without migration (It.73 Classic baseline).
 */
final class ClassicSingleLocaleCompatibilityTest extends TestCase
{
    public function testPublicHomePageRemainsReadableWithoutSchemaV2(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/pages/home'));
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('home', $data['data']['slug'] ?? null);
        $this->assertNotSame('', trim((string) ($data['data']['title'] ?? '')));
        $this->assertSame('published', $data['data']['status'] ?? null);
        $this->assertArrayHasKey('_locale', $data['data']);
        $this->assertSame('sk', $data['data']['_locale']['resolved'] ?? null);
    }

    public function testLegacyListPaginationStillWorks(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/pages?page=1&per_page=5')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertGreaterThan(0, $data['meta']['total'] ?? 0);
    }
}
