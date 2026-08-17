<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Snippets;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Layout\Services\ShortcodeExpanderService;
use PaginiumCMS\Core\Layout\Services\ShortcodeRegistry;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Snippets\Services\SnippetCatalogSeeder;
use PaginiumCMS\Core\Snippets\Services\SnippetReferenceScanner;
use PaginiumCMS\Core\Snippets\Services\SnippetRegistry;
use PaginiumCMS\Core\Snippets\Services\SnippetRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class SnippetRepositoryTest extends TestCase
{
    private string $baseDir;
    private SnippetRepository $repository;
    private ShortcodeExpanderService $expander;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_snippet_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $registry = new SnippetRegistry($reader, $writer);

        $settings = new SettingsRepository(
            $writer,
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );

        $this->repository = new SnippetRepository(
            $registry,
            $reader,
            $writer,
            new ContentSecuritySanitizer($settings)
        );

        $this->expander = new ShortcodeExpanderService(
            new ShortcodeRegistry($reader, $writer),
            $reader,
            new ContentSecuritySanitizer($settings),
            $this->repository
        );

        (new SnippetCatalogSeeder($this->repository, $registry))->seedIfEmpty();
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testCatalogSeederCreatesBundledSnippets(): void
    {
        $items = $this->repository->list();

        $this->assertGreaterThanOrEqual(2, count($items));
        $names = array_column($items, 'name');
        $this->assertContains('author-bio', $names);
        $this->assertContains('cta-banner', $names);
    }

    public function testExpanderInlinesSnippetReference(): void
    {
        $result = $this->expander->expand('Intro [snippet name="author-bio"/] tail');

        $this->assertStringContainsString('Jane Doe', $result);
        $this->assertStringNotContainsString('[snippet', $result);
    }

    public function testReferenceScannerFindsEmbeddingContent(): void
    {
        $pagesDir = $this->baseDir . '/content/pages';
        mkdir($pagesDir, 0777, true);
        file_put_contents(
            $pagesDir . '/about.md',
            "---\ntitle: About\n---\n\nTeam page [snippet name=\"author-bio\"/]\n"
        );

        $scanner = new SnippetReferenceScanner(new FileReader(new FileValidator($this->baseDir)));
        $refs = $scanner->findReferences('author-bio');

        $this->assertCount(1, $refs);
        $this->assertSame('page', $refs[0]['type']);
        $this->assertSame('about', $refs[0]['slug']);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
