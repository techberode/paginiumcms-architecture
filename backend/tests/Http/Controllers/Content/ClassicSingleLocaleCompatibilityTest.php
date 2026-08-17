<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Content;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use PaginiumCMS\Tests\Http\TestCase;

/**
 * Ensures legacy single-locale content remains readable without migration (It.73 Classic baseline).
 *
 * CI has no committed pages under storage/app/content/pages/ (gitignored), so each run seeds
 * a demo-like legacy home.md when missing.
 */
final class ClassicSingleLocaleCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureLegacyHomePageExists();
    }

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
        $this->assertNotSame(2, $data['data']['schemaVersion'] ?? null);
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

    private function ensureLegacyHomePageExists(): void
    {
        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        $existing = $repo->findBySlug('home', 'page');
        if ($existing !== null && (int) ($existing->getFrontMatter()['schemaVersion'] ?? 1) < 2) {
            return;
        }

        $fixture = DemoFixtures::seedFiles()['pages/home.md'] ?? null;
        $this->assertIsString($fixture, 'DemoFixtures must provide legacy pages/home.md');

        $writer = $this->app->getContainer()->get(FileWriterInterface::class);
        $writer->write('pages/home.md', $fixture, true);

        $page = $repo->findByPath('pages/home.md');
        $this->assertNotNull($page, 'Seeded legacy home page must be readable from disk');

        $index = $this->app->getContainer()->get(ContentIndexService::class);
        $index->upsertFromContent($page, 'page');
    }
}
