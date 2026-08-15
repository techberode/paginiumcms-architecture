<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Content\LocalizedContentMigrationService;
use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\LocalizedContentWriter;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
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
use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use PaginiumCMS\Tests\Support\GitPublishTestHelper;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Demo-like SK legacy tree → schema v2 migration → EN locale round-trip → rollback (It.73 DoD).
 */
final class DemoLocaleMigrationIntegrationTest extends TestCase
{
    private LocalizedContentMigrationService $migration;
    private ContentRepository $repository;
    private LocalizedContentNormalizer $normalizer;
    private LocalizedContentWriter $writer;
    private string $root;

    protected function setUp(): void
    {
        $pages = [];
        foreach (['home.md', 'about.md', 'contact.md'] as $file) {
            $key = 'pages/' . $file;
            $seed = DemoFixtures::seedFiles()[$key] ?? null;
            if (is_string($seed)) {
                $pages[$file] = $seed;
            }
        }

        vfsStream::setup('demo-migration', null, ['pages' => $pages]);
        $this->root = vfsStream::url('demo-migration');

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
        $this->normalizer = new LocalizedContentNormalizer($settings);
        $index = new ContentIndexService($reader, $this->normalizer, new ContentStalenessService($settings), 'data/index/content.json');

        $this->repository = new ContentRepository(
            $reader,
            $writer,
            $index,
            $markdownStorage,
            $jsonStorage,
            $settings,
            StorageTestHelper::localStorage($this->root),
            GitPublishTestHelper::disabledDispatcher($reader, $writer, $settings),
            new LocalizedContentWriter($this->normalizer)
        );

        $this->writer = new LocalizedContentWriter($this->normalizer);
        $cache = $this->createMock(ContentCacheService::class);

        $this->migration = new LocalizedContentMigrationService(
            $reader,
            $writer,
            $this->repository,
            $index,
            $cache,
            $this->normalizer,
            $this->writer,
            $settings
        );
    }

    public function testDemoSkLegacyMigrationRoundTripWithEnglishLocale(): void
    {
        $snapshots = [];
        foreach (['pages/home.md', 'pages/about.md', 'pages/contact.md'] as $path) {
            $content = $this->repository->findByPath($path);
            $this->assertNotNull($content, $path);
            $snapshots[$path] = [
                'title' => $content->getTitle(),
                'body' => $content->getContent(),
                'status' => $content->getStatus(),
            ];
        }

        $inventory = $this->migration->inventory();
        $this->assertSame(3, $inventory['totals']['legacySingleLocale']);
        $this->assertFalse($inventory['blockingConflicts']);

        $dryRun = $this->migration->dryRun('sk');
        $this->assertSame(3, $dryRun['wouldConvert']);

        $beforeHome = file_get_contents($this->root . '/pages/home.md');
        $this->assertIsString($beforeHome);

        $run = $this->migration->run('sk', 'demo-sk-en-migration', true);
        $this->assertSame(3, $run['converted']);
        $this->assertCount(3, $run['verified']);
        foreach ($run['verified'] as $item) {
            $this->assertTrue($item['ok']);
        }

        $home = $this->repository->findByPath('pages/home.md');
        $this->assertNotNull($home);
        $this->assertSame(2, $home->getFrontMatter()['schemaVersion']);
        $this->assertSame('Demo domov', $home->getFrontMatter()['localizedContent']['sk']['title']);

        $about = $this->repository->findByPath('pages/about.md');
        $this->assertNotNull($about);
        $this->writer->applyLocalePayload($about, [
            'locale' => 'en',
            'title' => 'About the demo module',
            'content' => "The demo module provides a safe sandbox.\n",
            'status' => 'draft',
        ], 'about');
        $this->repository->save($about);

        $canonical = $this->normalizer->normalize($about);
        $this->assertSame('O demo module', $canonical['localizedContent']['sk']['title']);
        $this->assertSame('About the demo module', $canonical['localizedContent']['en']['title']);
        $this->assertSame('draft', $canonical['localeStatus']['en']);

        foreach ($snapshots as $path => $expected) {
            $current = $this->repository->findByPath($path);
            $this->assertNotNull($current);
            $normalized = $this->normalizer->normalize($current);
            $this->assertSame($expected['title'], $normalized['localizedContent']['sk']['title']);
            $this->assertSame($expected['body'], $normalized['localizedContent']['sk']['body']);
            $this->assertSame($expected['status'], $normalized['localeStatus']['sk']);
        }

        $rollback = $this->migration->rollback('demo-sk-en-migration', true);
        $this->assertSame(3, $rollback['restored']);
        $this->assertSame($beforeHome, file_get_contents($this->root . '/pages/home.md'));

        $legacyHome = $this->repository->findByPath('pages/home.md');
        $this->assertNotNull($legacyHome);
        $this->assertSame(1, $this->normalizer->normalize($legacyHome)['schemaVersion']);
        $this->assertSame('Demo domov', $legacyHome->getTitle());
    }
}
