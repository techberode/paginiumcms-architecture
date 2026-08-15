<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\Blueprint\Services\BlueprintRepository;
use PaginiumCMS\Core\Blueprint\Services\DynamicValidator;
use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\LocalizedContentWriter;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\ContentDuplicationService;
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
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Git\Services\GitPublishDispatcher;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Tests\Support\GitPublishTestHelper;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class ContentDuplicationServiceTest extends TestCase
{
    private ContentDuplicationService $service;
    private ContentRepository $repository;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, [
            'content' => [
                'pages' => [
                    'source-page.md' => "---\ntitle: Source Page\nslug: source-page\nstatus: published\nscheduledAt: 2026-08-01T10:00:00+02:00\npublishApprovedAt: 2026-07-31T10:00:00+02:00\ndate: 2026-01-01 10:00:00\ntags: [guide]\n---\n# Source",
                ],
                'blog' => [
                    'source-article.md' => "---\ntitle: Source Article\nslug: source-article\nstatus: scheduled\nschemaVersion: 2\ndefaultLocale: sk\nlocaleStatus:\n  sk: published\n  en: draft\nlocalizedContent:\n  sk:\n    title: SK title\n    body: SK body\n  en:\n    title: EN title\n    body: EN body\n---\n# Legacy body",
                    'source-article-copy.md' => "---\ntitle: Existing copy\nslug: source-article-copy\nstatus: draft\n---\n# Existing",
                ],
            ],
        ]);

        $root = vfsStream::url('storage');
        $validator = new FileValidator($root . '/content');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $frontMatterParser = new FrontMatterParser();
        $contentParser = new MarkdownContentParser();
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(static fn (string $key, mixed $default = null): mixed => match ($key) {
            'general.language' => 'sk',
            default => $default,
        });
        $settings->method('group')->willReturn(['sanitizeHtmlOnSave' => false]);

        $bodyRenderer = new ContentBodyRenderer(
            $contentParser,
            new TiptapHtmlRenderer(),
            new ContentSecuritySanitizer($settings)
        );
        $markdownParser = new MarkdownParser($frontMatterParser, $bodyRenderer);
        $markdownStorage = new MarkdownContentStorage($markdownParser);
        $jsonStorage = new JsonContentStorage($bodyRenderer);
        $normalizer = new LocalizedContentNormalizer($settings);
        $staleness = new ContentStalenessService($settings);
        $index = new ContentIndexService($reader, $normalizer, $staleness, 'data/index/content.json');

        $this->repository = new ContentRepository(
            $reader,
            $writer,
            $index,
            $markdownStorage,
            $jsonStorage,
            $settings,
            StorageTestHelper::localStorage($root . '/content'),
            GitPublishTestHelper::disabledDispatcher($reader, $writer, $settings),
            new LocalizedContentWriter($normalizer)
        );
        $index->rebuild($this->repository);

        $this->service = new ContentDuplicationService(
            $this->repository,
            new DynamicValidator(new BlueprintRepository($reader), new Validator())
        );
    }

    public function testDuplicatePageCreatesDraftWithCopySlugAndTitle(): void
    {
        $source = $this->repository->findBySlug('source-page', 'page');
        $this->assertInstanceOf(Page::class, $source);

        $duplicate = $this->service->createDuplicate($source, 'page');
        $this->repository->save($duplicate);

        $this->assertSame('source-page-copy', $duplicate->getSlug());
        $this->assertSame('draft', $duplicate->getStatus());
        $this->assertSame('Source Page (copy)', $duplicate->getTitle());
        $this->assertNull($duplicate->getScheduledAt());
        $this->assertNull($duplicate->getDate());
        $this->assertSame(['guide'], $duplicate->getTags());
        $this->assertSame('# Source', $duplicate->getContent());

        $stored = $this->repository->findBySlug('source-page-copy', 'page');
        $this->assertNotNull($stored);
        $this->assertSame('draft', $stored->getStatus());
    }

    public function testDuplicateArticleIncrementsCopySlugWhenTaken(): void
    {
        $source = $this->repository->findBySlug('source-article', 'article');
        $this->assertInstanceOf(Article::class, $source);

        $duplicate = $this->service->createDuplicate($source, 'article');

        $this->assertSame('source-article-copy-2', $duplicate->getSlug());
        $this->assertSame('draft', $duplicate->getStatus());

        $frontMatter = $duplicate->getFrontMatter();
        $this->assertSame('draft', $frontMatter['localeStatus']['sk'] ?? null);
        $this->assertSame('draft', $frontMatter['localeStatus']['en'] ?? null);
        $this->assertSame('SK title (copy)', $frontMatter['localizedContent']['sk']['title'] ?? null);
        $this->assertSame('EN title (copy)', $frontMatter['localizedContent']['en']['title'] ?? null);
    }

    public function testDuplicateRejectsExistingRequestedSlug(): void
    {
        $source = $this->repository->findBySlug('source-page', 'page');
        $this->assertNotNull($source);

        $this->expectException(FlatFileException::class);
        $this->service->createDuplicate($source, 'page', ['newSlug' => 'source-page']);
    }

    public function testDuplicateRejectsInvalidRequestedSlug(): void
    {
        $source = $this->repository->findBySlug('source-page', 'page');
        $this->assertNotNull($source);

        $this->expectException(FlatFileException::class);
        $this->service->createDuplicate($source, 'page', ['newSlug' => 'Invalid Slug!']);
    }
}
