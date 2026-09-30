<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\Services\ContentInternalLinkCheckService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PHPUnit\Framework\TestCase;

final class ContentInternalLinkCheckServiceTest extends TestCase
{
    public function testFlagsMissingInternalPage(): void
    {
        $repo = $this->createMock(ContentRepositoryInterface::class);
        $repo->method('findBySlug')->willReturnCallback(static function (string $slug, string $type): ?Page {
            if ($type === 'page' && $slug === 'exists') {
                return new Page();
            }

            return null;
        });

        $service = new ContentInternalLinkCheckService($repo);
        $result = $service->check("See [missing](/ghost-page) here.", 'home', 'page');

        $this->assertFalse($result['ok']);
        $this->assertSame('missing_content', $result['issues'][0]['reason']);
    }

    public function testFlagsAbsoluteBlogUrl(): void
    {
        $repo = $this->createMock(ContentRepositoryInterface::class);
        $repo->method('findBySlug')->willReturn(null);

        $service = new ContentInternalLinkCheckService($repo);
        $result = $service->check(
            '[x](https://dev.example.test/blog/missing-slug)',
            'home',
            'page'
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('missing_content', $result['issues'][0]['reason']);
    }

    public function testFlagsUnpublishedArticleTarget(): void
    {
        $draft = (new Article())->setFrontMatter(['slug' => 'draft-post', 'status' => 'draft']);

        $repo = $this->createMock(ContentRepositoryInterface::class);
        $repo->method('findBySlug')->willReturnCallback(static function (string $slug, string $type) use ($draft): ?Content {
            if ($type === 'article' && $slug === 'draft-post') {
                return $draft;
            }

            return null;
        });

        $service = new ContentInternalLinkCheckService($repo);
        $result = $service->check('[read](/blog/draft-post)', 'home', 'page');

        $this->assertFalse($result['ok']);
        $this->assertSame('not_published', $result['issues'][0]['reason']);
    }

    public function testFlagsRelativeArticleSlugInMarkdown(): void
    {
        $published = (new Article())->setFrontMatter(['slug' => 'project-site-planner', 'status' => 'published']);

        $repo = $this->createMock(ContentRepositoryInterface::class);
        $repo->method('findBySlug')->willReturnCallback(static function (string $slug, string $type) use ($published): ?Content {
            if ($type === 'article' && $slug === 'project-site-planner') {
                return $published;
            }

            return null;
        });

        $body = '[Predchádzajúci diel](./project-site-planner-test).';
        $service = new ContentInternalLinkCheckService($repo);
        $result = $service->check($body, 'beta-47', 'article');

        $this->assertFalse($result['ok']);
        $this->assertSame('./project-site-planner-test', $result['issues'][0]['url']);
        $this->assertSame('missing_content', $result['issues'][0]['reason']);
    }

    public function testAcceptsRelativeArticleSlugWhenTargetExists(): void
    {
        $published = (new Article())->setFrontMatter(['slug' => 'project-site-planner', 'status' => 'published']);

        $repo = $this->createMock(ContentRepositoryInterface::class);
        $repo->method('findBySlug')->willReturnCallback(static function (string $slug, string $type) use ($published): ?Content {
            if ($type === 'article' && $slug === 'project-site-planner') {
                return $published;
            }

            return null;
        });

        $body = '[Predchádzajúci diel](./project-site-planner).';
        $service = new ContentInternalLinkCheckService($repo);
        $result = $service->check($body, 'beta-47', 'article');

        $this->assertTrue($result['ok']);
    }

    public function testExtractsLinkFromTiptapJson(): void
    {
        $repo = $this->createMock(ContentRepositoryInterface::class);
        $repo->method('findBySlug')->willReturn(null);

        $json = json_encode([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Go',
                            'marks' => [
                                ['type' => 'link', 'attrs' => ['href' => '/blog/ghost']],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $service = new ContentInternalLinkCheckService($repo);
        $result = $service->check($json, 'home', 'page', 'tiptap_json');

        $this->assertFalse($result['ok']);
        $this->assertSame('/blog/ghost', $result['issues'][0]['url']);
    }
}
