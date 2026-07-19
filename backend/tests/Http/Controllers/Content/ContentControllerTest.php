<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Content;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Page;
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

    public function testListPagesPaginatedSurvivesCacheRoundTrip(): void
    {
        $request = $this->createJsonRequest('GET', '/api/pages?page=1&per_page=10');

        $first = $this->getJsonResponse($this->handleRequest($request));
        $second = $this->getJsonResponse($this->handleRequest($request));

        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);
        $this->assertSame($first['meta']['total'] ?? null, $second['meta']['total'] ?? null);
        $this->assertCount(count($first['data']), $second['data']);

        if (($first['data'][0] ?? null) !== null) {
            $this->assertArrayHasKey('slug', $first['data'][0]);
            $this->assertSame($first['data'][0]['slug'], $second['data'][0]['slug']);
        }
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

    public function testBulkDeletePagesRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages/bulk-delete', ['slugs' => ['test-slug']])
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testBulkDeleteAndBulkStatusPages(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        $slugA = 'bulk-a-' . uniqid();
        $slugB = 'bulk-b-' . uniqid();

        foreach ([$slugA, $slugB] as $slug) {
            $page = new Page();
            $page->setSlug($slug);
            $page->setFrontMatter([
                'title' => 'Bulk ' . $slug,
                'slug' => $slug,
                'status' => 'draft',
            ]);
            $page->setContent("# Bulk\n");
            $repo->save($page);
        }

        $statusResponse = $this->handleRequest(
            $this->createJsonRequest('PATCH', '/api/pages/bulk-status', [
                'slugs' => [$slugA, $slugB],
                'status' => 'published',
            ])
        );
        $statusData = $this->getJsonResponse($statusResponse);

        $this->assertSame(200, $statusResponse->getStatusCode());
        $this->assertTrue($statusData['success']);
        $this->assertSame(2, $statusData['data']['succeeded']);

        $deleteResponse = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages/bulk-delete', [
                'slugs' => [$slugA, $slugB],
            ])
        );
        $deleteData = $this->getJsonResponse($deleteResponse);

        $this->assertSame(200, $deleteResponse->getStatusCode());
        $this->assertTrue($deleteData['success']);
        $this->assertSame(2, $deleteData['data']['succeeded']);
    }

    public function testCreatePageRejectsInvalidSlugViaBlueprint(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages', [
                'title' => 'Bad slug page',
                'slug' => 'Invalid Slug!',
                'status' => 'draft',
                'content' => 'Test',
            ])
        );

        $this->assertSame(400, $response->getStatusCode());
    }
}
