<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Seo;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Tests\Http\TestCase;

final class SeoControllerTest extends TestCase
{
    public function testInvalidTypeReturns400(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/seo/invalid/foo')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function testUnknownSlugReturns404(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/seo/page/nonexistent-' . uniqid('', true))
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function testPublishedPageReturnsSeoMetaPayload(): void
    {
        $slug = 'seo-test-' . uniqid('', true);
        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        $page = new Page();
        $page->setSlug($slug);
        $page->setFrontMatter([
            'title' => 'SEO Test Page',
            'slug' => $slug,
            'status' => 'published',
            'description' => 'Meta description for SEO test.',
        ]);
        $page->setContent("# SEO Test\n");
        $repo->save($page);

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/seo/page/' . rawurlencode($slug))
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('SEO Test Page', (string) ($data['data']['title'] ?? ''));
        $this->assertSame('Meta description for SEO test.', $data['data']['description']);
        $this->assertArrayHasKey('canonical', $data['data']);
        $this->assertArrayHasKey('openGraph', $data['data']);
        $this->assertArrayHasKey('twitter', $data['data']);
        $this->assertSame('WebPage', $data['data']['jsonLd']['@type'] ?? null);
    }

    public function testDraftPageReturns404ForAnonymous(): void
    {
        $slug = 'seo-draft-' . uniqid('', true);
        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        $page = new Page();
        $page->setSlug($slug);
        $page->setFrontMatter([
            'title' => 'Draft',
            'slug' => $slug,
            'status' => 'draft',
        ]);
        $page->setContent("# Draft\n");
        $repo->save($page);

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/seo/page/' . rawurlencode($slug))
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }
}
