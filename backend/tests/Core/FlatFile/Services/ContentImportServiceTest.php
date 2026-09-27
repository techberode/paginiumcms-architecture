<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Services\ContentImportService;
use PaginiumCMS\Core\Import\ContentImportSourceRegistry;
use PaginiumCMS\Core\Import\ContentMigrationArchiveExtractor;
use PaginiumCMS\Core\Import\GhostJsonImporter;
use PaginiumCMS\Core\Import\GravPagesImporter;
use PaginiumCMS\Core\Import\HugoSiteImporter;
use PaginiumCMS\Core\Import\JekyllSiteImporter;
use PaginiumCMS\Core\Import\MarkdownSiteImportScanner;
use PaginiumCMS\Core\Import\WordPressWxrImporter;
use PaginiumCMS\Core\FlatFile\Services\FrontMatterParser;
use PaginiumCMS\Core\Security\Services\ZipEntryGuard;
use PHPUnit\Framework\TestCase;

final class ContentImportServiceTest extends TestCase
{
    private function createImportService(ContentRepositoryInterface $repo): ContentImportService
    {
        $scanner = new MarkdownSiteImportScanner(new FrontMatterParser());

        return new ContentImportService(
            $repo,
            new ContentImportSourceRegistry(
                new WordPressWxrImporter(),
                new GravPagesImporter($scanner),
                new JekyllSiteImporter($scanner),
                new HugoSiteImporter($scanner),
                new GhostJsonImporter(),
                new ContentMigrationArchiveExtractor(new ZipEntryGuard()),
            )
        );
    }

    public function testDryRunDoesNotSave(): void
    {
        $repo = $this->createMock(ContentRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findBySlug')
            ->with('sample', 'article')
            ->willReturn(null);
        $repo->expects($this->never())->method('save');

        $service = $this->createImportService($repo);
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

        $service = $this->createImportService($repo);
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
