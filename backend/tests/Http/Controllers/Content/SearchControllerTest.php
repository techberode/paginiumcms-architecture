<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Content;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Tests\Http\TestCase;

final class SearchControllerTest extends TestCase
{
    public function testPublicSearchReturnsPublishedMatches(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/search?q=home&scope=public')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
    }

    public function testAdminSearchRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/search?q=set&scope=admin')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAdminSearchReturnsGroupedPayload(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/search?q=set&scope=admin&types=page,route')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('admin', $data['data']['scope'] ?? null);
        $this->assertArrayHasKey('results', $data['data']);
        $this->assertArrayHasKey('counts', $data['data']);
    }

    public function testAdminSearchIncludesDraftPages(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);

        $slug = 'seo-draft-page-test';
        $searchToken = 'draftpalettekeyword';
        $page = new Page();
        $page->setFrontMatter([
            'slug' => $slug,
            'title' => 'SEO ' . $searchToken,
            'status' => 'draft',
        ]);
        $page->setContent('# draft search target ' . $searchToken);
        $repo->save($page);

        $index = $this->app->getContainer()->get(ContentIndexService::class);
        $index->rebuild($repo);

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/search?q=' . rawurlencode($searchToken) . '&scope=admin&types=page')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);

        $results = $data['data']['results'] ?? [];
        $this->assertNotEmpty($results, 'Expected at least one admin page search result for draft content.');

        $slugs = array_map(
            static fn (array $row): string => (string) ($row['slug'] ?? ''),
            $results
        );
        $this->assertContains($slug, $slugs);
    }
}
