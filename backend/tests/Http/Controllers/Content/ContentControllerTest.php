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

    public function testPublicListArticlesPaginatedIncludesTagMeta(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/articles?page=1&per_page=10&tag=news')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('tags', $data['meta']);
        $this->assertArrayHasKey('total_published', $data['meta']);
        $this->assertIsArray($data['meta']['tags']);

        foreach ($data['data'] as $item) {
            $this->assertSame('published', $item['status'] ?? null);
            $this->assertContains('news', $item['tags'] ?? []);
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

    public function testCreatePageAllowsMarkdownImageForMinimalProfile(): void
    {
        $this->loginAsAdminUser();

        $slug = 'profile-test-' . uniqid();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages', [
                'title' => 'Profile validation',
                'slug' => $slug,
                'status' => 'draft',
                'content' => 'Hello ![x](/a.png)',
                'contentFormat' => 'markdown',
                'editorProfile' => 'minimal',
            ])
        );

        $this->assertSame(201, $response->getStatusCode());
        $data = $this->getJsonResponse($response);
        $this->assertTrue($data['success']);
    }

    public function testCreatePageRejectsRawHtmlInMarkdown(): void
    {
        $this->loginAsAdminUser();

        $slug = 'security-test-' . uniqid();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages', [
                'title' => 'Security validation',
                'slug' => $slug,
                'status' => 'draft',
                'content' => "Hello\n\n<div>raw</div>",
                'contentFormat' => 'markdown',
                'editorProfile' => 'blog',
            ])
        );

        $this->assertSame(400, $response->getStatusCode());
        $data = $this->getJsonResponse($response);
        $this->assertFalse($data['success']);
    }

    public function testCreatePageWithScheduledStatusRequiresScheduledAt(): void
    {
        $this->loginAsAdminUser();
        $slug = 'scheduled-test-' . uniqid('', true);

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages', [
                'title' => 'Scheduled page',
                'slug' => $slug,
                'status' => 'scheduled',
                'content' => 'Scheduled body',
            ])
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreatePageWithScheduledAtStoresScheduledStatus(): void
    {
        $this->loginAsAdminUser();
        $slug = 'scheduled-save-' . uniqid('', true);
        $scheduledAt = (new \DateTimeImmutable('+1 day'))->format('c');

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages', [
                'title' => 'Scheduled page',
                'slug' => $slug,
                'status' => 'scheduled',
                'scheduledAt' => $scheduledAt,
                'content' => 'Scheduled body',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('scheduled', $data['data']['status'] ?? null);
        $this->assertSame($scheduledAt, $data['data']['scheduledAt'] ?? null);
    }

    public function testPublicGetScheduledPageReturns404(): void
    {
        $this->loginAsAdminUser();
        $slug = 'scheduled-hidden-' . uniqid('', true);
        $scheduledAt = (new \DateTimeImmutable('+1 day'))->format('c');

        $create = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages', [
                'title' => 'Hidden scheduled',
                'slug' => $slug,
                'status' => 'scheduled',
                'scheduledAt' => $scheduledAt,
                'content' => 'Hidden',
            ])
        );
        $this->assertSame(201, $create->getStatusCode());

        $_SESSION = [];
        $this->currentUser = null;
        $public = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/pages/' . $slug)
        );
        $this->assertSame(404, $public->getStatusCode());
    }

    public function testCreatePageWithLocaleScopeWritesSchemaV2(): void
    {
        $this->loginAsAdminUser();
        $slug = 'locale-create-' . uniqid('', true);

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages', [
                'locale' => 'en',
                'title' => 'English title',
                'slug' => $slug,
                'status' => 'draft',
                'content' => 'English body',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame(2, $data['data']['schemaVersion'] ?? null);
        $this->assertSame('English title', $data['data']['localizedContent']['en']['title'] ?? null);
        $this->assertSame('draft', $data['data']['localeStatus']['en'] ?? null);
    }

    public function testUpdatePageWithLocaleScopeMergesSecondLocale(): void
    {
        $this->loginAsAdminUser();
        $slug = 'locale-merge-' . uniqid('', true);

        $create = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages', [
                'locale' => 'sk',
                'title' => 'SK title',
                'slug' => $slug,
                'status' => 'published',
                'content' => 'SK body',
            ])
        );
        $created = $this->getJsonResponse($create);
        $revision = $created['data']['revision'] ?? '';

        $update = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/pages/' . $slug, [
                'locale' => 'en',
                'title' => 'EN title',
                'slug' => $slug,
                'status' => 'draft',
                'content' => 'EN body',
                'baseRevision' => $revision,
            ])
        );
        $updated = $this->getJsonResponse($update);

        $this->assertSame(200, $update->getStatusCode());
        $this->assertTrue($updated['success']);
        $this->assertSame('SK title', $updated['data']['localizedContent']['sk']['title'] ?? null);
        $this->assertSame('EN title', $updated['data']['localizedContent']['en']['title'] ?? null);
        $this->assertSame('published', $updated['data']['localeStatus']['sk'] ?? null);
        $this->assertSame('draft', $updated['data']['localeStatus']['en'] ?? null);
    }

    public function testPublishLocaleWithoutTitleReturns400(): void
    {
        $this->loginAsAdminUser();
        $slug = 'locale-invalid-' . uniqid('', true);

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages', [
                'locale' => 'en',
                'title' => '',
                'slug' => $slug,
                'status' => 'published',
                'content' => '',
            ])
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testPatchPageStatusWithLocaleScopeUpdatesLocaleStatusOnly(): void
    {
        $this->loginAsAdminUser();
        $slug = 'locale-patch-' . uniqid('', true);

        $create = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages', [
                'locale' => 'sk',
                'title' => 'SK title',
                'slug' => $slug,
                'status' => 'published',
                'content' => 'SK body',
            ])
        );
        $this->assertSame(201, $create->getStatusCode());

        $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/pages/' . $slug, [
                'locale' => 'en',
                'title' => 'EN title',
                'slug' => $slug,
                'status' => 'draft',
                'content' => 'EN body',
                'baseRevision' => $this->getJsonResponse($create)['data']['revision'] ?? '',
            ])
        );

        $patch = $this->handleRequest(
            $this->createJsonRequest('PATCH', '/api/pages/' . $slug . '/status', [
                'locale' => 'en',
                'status' => 'published',
            ])
        );
        $data = $this->getJsonResponse($patch);

        $this->assertSame(200, $patch->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('published', $data['data']['localeStatus']['en'] ?? null);
        $this->assertSame('published', $data['data']['localeStatus']['sk'] ?? null);
    }
}
