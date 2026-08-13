<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Services\ContentImportService;
use PaginiumCMS\Core\Import\WordPressWxrImporter;
use PHPUnit\Framework\TestCase;

final class ContentImportServiceTest extends TestCase
{
    public function testDryRunDoesNotSave(): void
    {
        $repo = $this->createMock(ContentRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findBySlug')
            ->with('sample', 'article')
            ->willReturn(null);
        $repo->expects($this->never())->method('save');

        $service = new ContentImportService($repo, new WordPressWxrImporter());
        $result = $service->importFromJsonPayload([
            'items' => [[
                'type' => 'article',
                'slug' => 'sample',
                'frontMatter' => ['title' => 'Sample', 'status' => 'draft'],
                'content' => 'Hello',
            ]],
        ], true);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(1, $result->created);
    }

    public function testRunPersistsContent(): void
    {
        $repo = $this->createMock(ContentRepositoryInterface::class);
        $repo->method('findBySlug')->willReturn(null);
        $repo->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn ($content): bool => $content instanceof Article && $content->getSlug() === 'sample'));

        $service = new ContentImportService($repo, new WordPressWxrImporter());
        $result = $service->importFromJsonPayload([
            'items' => [[
                'type' => 'article',
                'slug' => 'sample',
                'frontMatter' => ['title' => 'Sample', 'status' => 'published'],
                'content' => 'Hello',
            ]],
        ], false);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(1, $result->created);
    }
}
