<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Content\LocalizedContentMigrationService;
use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\LocalizedContentWriter;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentRepository;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FrontMatterParser;
use PaginiumCMS\Core\FlatFile\Services\JsonContentStorage;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentStorage;
use PaginiumCMS\Core\FlatFile\Services\MarkdownParser;
use PaginiumCMS\Core\Editor\Services\ContentBodyRenderer;
use PaginiumCMS\Core\Editor\Services\TiptapHtmlRenderer;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Support\GitPublishTestHelper;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class LocalizedContentMigrationServiceTest extends TestCase
{
    private LocalizedContentMigrationService $migration;
    private ContentRepository $repository;
    private string $root;

    protected function setUp(): void
    {
        $structure = [
            'pages' => [
                'legacy.md' => "---\ntitle: Legacy\nslug: legacy\nstatus: published\n---\n# Legacy body",
                'already-v2.json' => json_encode([
                    'schemaVersion' => 2,
                    'defaultLocale' => 'sk',
                    'slug' => 'already-v2',
                    'status' => 'published',
                    'localizedContent' => [
                        'sk' => ['title' => 'V2', 'body' => 'Body', 'seo' => ['title' => '', 'description' => '', 'canonical' => '', 'ogImage' => '', 'noIndex' => false]],
                    ],
                    'localeStatus' => ['sk' => 'published'],
                ], JSON_THROW_ON_ERROR),
            ],
        ];

        vfsStream::setup('storage', null, $structure);
        $this->root = vfsStream::url('storage');

        $validator = new FileValidator($this->root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(static fn (string $key, mixed $default = null): mixed => $key === 'general.language' ? 'sk' : $default);
        $settings->method('group')->willReturn(['sanitizeHtmlOnSave' => false]);

        $bodyRenderer = new ContentBodyRenderer(
            new MarkdownContentParser(),
            new TiptapHtmlRenderer(),
            new ContentSecuritySanitizer($settings)
        );
        $markdownParser = new MarkdownParser(new FrontMatterParser(), $bodyRenderer);
        $markdownStorage = new MarkdownContentStorage($markdownParser);
        $jsonStorage = new JsonContentStorage($bodyRenderer);
        $normalizer = new LocalizedContentNormalizer($settings);
        $index = new ContentIndexService($reader, $normalizer, 'data/index/content.json');

        $this->repository = new ContentRepository(
            $reader,
            $writer,
            $index,
            $markdownStorage,
            $jsonStorage,
            $settings,
            StorageTestHelper::localStorage($this->root),
            GitPublishTestHelper::disabledDispatcher($reader, $writer, $settings)
        );

        $cache = $this->createMock(ContentCacheService::class);

        $this->migration = new LocalizedContentMigrationService(
            $reader,
            $writer,
            $this->repository,
            $index,
            $cache,
            $normalizer,
            new LocalizedContentWriter($normalizer),
            $settings
        );
    }

    public function testInventoryFindsLegacyAndSchemaV2Documents(): void
    {
        $report = $this->migration->inventory();

        $this->assertSame(2, $report['totals']['documents']);
        $this->assertSame(1, $report['totals']['legacySingleLocale']);
        $this->assertSame(1, $report['totals']['schemaV2']);
        $this->assertFalse($report['blockingConflicts']);
    }

    public function testDryRunListsLegacyCandidateWithoutWriting(): void
    {
        $before = file_get_contents($this->root . '/pages/legacy.md');
        $report = $this->migration->dryRun('sk');

        $this->assertSame(1, $report['wouldConvert']);
        $this->assertSame('sk', $report['defaultLocale']);
        $this->assertSame($before, file_get_contents($this->root . '/pages/legacy.md'));
    }

    public function testRunConvertsLegacyDocumentAndCreatesManifest(): void
    {
        $report = $this->migration->run('sk', 'test-migration-001', true);

        $this->assertSame(1, $report['converted']);
        $this->assertSame('test-migration-001', $report['migrationId']);

        $converted = $this->repository->findByPath('pages/legacy.md');
        $this->assertNotNull($converted);
        $this->assertSame(2, $converted->getFrontMatter()['schemaVersion']);
        $this->assertArrayHasKey('sk', $converted->getFrontMatter()['localizedContent']);

        $manifestPath = $this->root . '/data/migrations/test-migration-001/manifest.json';
        $this->assertFileExists($manifestPath);
        $backupPath = $this->root . '/data/migrations/test-migration-001/files/pages/legacy.md';
        $this->assertFileExists($backupPath);
    }

    public function testRollbackRestoresLegacyDocument(): void
    {
        $before = file_get_contents($this->root . '/pages/legacy.md');
        $this->migration->run('sk', 'test-migration-rollback', true);

        $converted = $this->repository->findByPath('pages/legacy.md');
        $this->assertNotNull($converted);
        $this->assertSame(2, $converted->getFrontMatter()['schemaVersion']);

        $rollback = $this->migration->rollback('test-migration-rollback', true);
        $this->assertSame(1, $rollback['restored']);
        $this->assertSame($before, file_get_contents($this->root . '/pages/legacy.md'));
    }

    public function testRunRequiresConfirmation(): void
    {
        $this->expectException(FlatFileException::class);
        $this->migration->run('sk', 'blocked-run', false);
    }

    public function testDetectsAmbiguousLocaleCopyPair(): void
    {
        $service = $this->createMigrationServiceWithExtraPage();
        $report = $service->inventory();

        $this->assertTrue($report['blockingConflicts']);
        $this->assertNotEmpty($report['conflicts']);
    }

    private function createMigrationServiceWithExtraPage(): LocalizedContentMigrationService
    {
        $structure = [
            'pages' => [
                'about.md' => "---\ntitle: About\nslug: about\nstatus: published\n---\n# SK",
                'about-en.md' => "---\ntitle: About EN\nslug: about\nstatus: draft\n---\n# EN",
            ],
        ];

        vfsStream::setup('storage-conflict', null, $structure);
        $root = vfsStream::url('storage-conflict');

        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('sk');
        $settings->method('group')->willReturn([]);
        $normalizer = new LocalizedContentNormalizer($settings);
        $index = new ContentIndexService($reader, $normalizer, 'data/index/content.json');
        $repository = new ContentRepository(
            $reader,
            $writer,
            $index,
            new MarkdownContentStorage(new MarkdownParser(new FrontMatterParser(), new ContentBodyRenderer(
                new MarkdownContentParser(),
                new TiptapHtmlRenderer(),
                new ContentSecuritySanitizer($settings)
            ))),
            new JsonContentStorage(new ContentBodyRenderer(
                new MarkdownContentParser(),
                new TiptapHtmlRenderer(),
                new ContentSecuritySanitizer($settings)
            )),
            $settings,
            StorageTestHelper::localStorage($root),
            GitPublishTestHelper::disabledDispatcher($reader, $writer, $settings)
        );

        return new LocalizedContentMigrationService(
            $reader,
            $writer,
            $repository,
            $index,
            $this->createMock(ContentCacheService::class),
            $normalizer,
            new LocalizedContentWriter($normalizer),
            $settings
        );
    }
}
