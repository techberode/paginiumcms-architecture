<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Content;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Tests\Http\TestCase;

final class EditorialCalendarControllerTest extends TestCase
{
    public function testEditorialCalendarRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/content/editorial-calendar?from=2026-08-01&to=2026-08-31')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testEditorialCalendarRequiresRange(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/content/editorial-calendar')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function testEditorialCalendarReturnsItemsInRange(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);

        $slug = 'calendar-test-article';
        $article = new Article();
        $article->setFrontMatter([
            'slug' => $slug,
            'title' => 'Calendar Test Article',
            'status' => 'scheduled',
            'scheduledAt' => '2026-08-20T10:00:00+02:00',
        ]);
        $article->setContent('# Calendar target');
        $repo->save($article);

        $pageSlug = 'calendar-test-page';
        $page = new Page();
        $page->setFrontMatter([
            'slug' => $pageSlug,
            'title' => 'Calendar Test Page',
            'status' => 'published',
            'createdAt' => '2026-08-18T10:00:00+02:00',
        ]);
        $page->setContent('# Calendar page');
        $repo->save($page);

        $index = $this->app->getContainer()->get(ContentIndexService::class);
        $index->rebuild($repo);

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/content/editorial-calendar?from=2026-08-01&to=2026-08-31&type=article')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);

        $slugs = array_map(static fn (array $row): string => (string) ($row['slug'] ?? ''), $data['data']);
        $this->assertContains($slug, $slugs);
        $this->assertNotContains($pageSlug, $slugs);
        $this->assertSame('2026-08-01', $data['meta']['from'] ?? null);
        $this->assertSame('2026-08-31', $data['meta']['to'] ?? null);
    }

    public function testEditorialCalendarRejectsOversizedRange(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/content/editorial-calendar?from=2026-01-01&to=2026-12-31')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }
}
