<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\StaticSite;

use PaginiumCMS\Core\Editor\Services\ContentBodyRenderer;
use PaginiumCMS\Core\Editor\Services\TiptapHtmlRenderer;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FrontMatterParser;
use PaginiumCMS\Core\FlatFile\Services\JsonContentStorage;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentStorage;
use PaginiumCMS\Core\FlatFile\Services\MarkdownParser;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\StaticSite\StaticSiteCompiler;
use PaginiumCMS\Core\StaticSite\StaticSiteGenerator;
use PaginiumCMS\Core\StaticSite\StaticSitePath;
use PaginiumCMS\Core\StaticSite\StaticSiteSettings;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class StaticSiteGeneratorTest extends TestCase
{
    public function testRebuildWritesPublishedHtmlAndSkipsDrafts(): void
    {
        $root = $this->bootTree();
        $generator = $this->makeGenerator($root, 'hybrid');

        $result = $generator->rebuildAll();

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['written']);
        $this->assertTrue($root['reader']->exists('static/pages/home/index.html'));
        $this->assertFalse($root['reader']->exists('static/pages/secret/index.html'));
        $html = $root['reader']->read('static/pages/home/index.html');
        $this->assertStringContainsString('<title>Home</title>', $html);
        $this->assertStringContainsString('Welcome', $html);
        $this->assertStringNotContainsString('.php', $html);
        $this->assertTrue($root['reader']->exists('static/manifest.json'));
    }

    public function testRebuildOneRemovesUnpublishedAndRejectsTraversalSlug(): void
    {
        $root = $this->bootTree();
        $generator = $this->makeGenerator($root, 'static');
        $generator->rebuildAll();

        $removed = $generator->rebuildOne('page', 'secret');
        $this->assertSame('removed', $removed['action']);

        $this->expectException(\InvalidArgumentException::class);
        $generator->rebuildOne('page', '../etc/passwd');
    }

    public function testPathHelperNeverAllowsPhpExtension(): void
    {
        $path = StaticSitePath::htmlRelative('page', 'about');
        $this->assertSame('static/pages/about/index.html', $path);
        $this->assertStringEndsWith('.html', $path);
        $this->assertStringNotContainsString('.php', $path);
    }

    /**
     * @return array{reader: FileReader, writer: FileWriter, renderer: ContentBodyRenderer, repo: \PaginiumCMS\Core\FlatFile\Services\ContentRepository, settings: SettingsRepositoryInterface}
     */
    private function bootTree(): array
    {
        $structure = [
            'content' => [
                'pages' => [
                    'home.md' => "---\ntitle: Home\nslug: home\nstatus: published\n---\nWelcome to the site.",
                    'secret.md' => "---\ntitle: Secret\nslug: secret\nstatus: draft\n---\nHidden draft.",
                ],
                'blog' => [],
            ],
        ];
        vfsStream::setup('static-site', null, $structure);
        $base = vfsStream::url('static-site/content');
        $validator = new FileValidator($base);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(static fn (string $key, mixed $default = null): mixed => $default);
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            if ($group === 'engine') {
                return ['renderMode' => 'hybrid'];
            }
            if ($group === 'general') {
                return ['language' => 'en'];
            }

            return ['sanitizeHtmlOnSave' => false];
        });
        $renderer = new ContentBodyRenderer(
            new MarkdownContentParser(),
            new TiptapHtmlRenderer(),
            new ContentSecuritySanitizer($settings)
        );
        $frontMatterParser = new FrontMatterParser();
        $markdownStorage = new MarkdownContentStorage(new MarkdownParser($frontMatterParser, $renderer));
        $jsonStorage = new JsonContentStorage($renderer);
        $normalizer = new \PaginiumCMS\Core\Content\LocalizedContentNormalizer($settings);
        $staleness = new \PaginiumCMS\Core\FlatFile\Services\ContentStalenessService($settings);
        $index = new \PaginiumCMS\Core\FlatFile\Services\ContentIndexService($reader, $normalizer, $staleness, 'data/index/content.json');
        $repo = new \PaginiumCMS\Core\FlatFile\Services\ContentRepository(
            $reader,
            $writer,
            $index,
            new \PaginiumCMS\Core\HybridEngine\QueryIndex\JsonQueryIndex($index),
            $markdownStorage,
            $jsonStorage,
            $settings,
            StorageTestHelper::localStorage($base),
            \PaginiumCMS\Tests\Support\GitPublishTestHelper::disabledDispatcher($reader, $writer, $settings),
            new \PaginiumCMS\Core\Content\LocalizedContentWriter($normalizer)
        );

        return [
            'reader' => $reader,
            'writer' => $writer,
            'renderer' => $renderer,
            'repo' => $repo,
            'settings' => $settings,
        ];
    }

    /**
     * @param array{reader: FileReader, writer: FileWriter, renderer: ContentBodyRenderer, repo: \PaginiumCMS\Core\FlatFile\Services\ContentRepository, settings: SettingsRepositoryInterface} $root
     */
    private function makeGenerator(array $root, string $mode): StaticSiteGenerator
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group) use ($mode): array {
            if ($group === 'engine') {
                return ['renderMode' => $mode];
            }

            return ['language' => 'en'];
        });
        $siteSettings = new StaticSiteSettings($settings);
        $compiler = new StaticSiteCompiler($root['reader'], $root['writer'], $siteSettings, $root['renderer']);

        return new StaticSiteGenerator($root['repo'], $root['reader'], $root['writer'], $siteSettings, $compiler);
    }

    public function testCompilerEscapesTitleAndWritesHtmlOnly(): void
    {
        $root = $this->bootTree();
        $settings = new StaticSiteSettings($root['settings']);
        $compiler = new StaticSiteCompiler($root['reader'], $root['writer'], $settings, $root['renderer']);
        $page = new Page();
        $page->setSlug('xss');
        $page->setTitle('<script>alert(1)</script>');
        $page->setStatus('published');
        $page->setContent('Safe body');
        $item = $compiler->writePublished($page, 'page');
        $this->assertNotNull($item);
        $html = $root['reader']->read($item['path']);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }
}
