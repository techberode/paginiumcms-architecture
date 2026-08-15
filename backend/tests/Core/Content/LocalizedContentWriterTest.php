<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\LocalizedContentWriter;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class LocalizedContentWriterTest extends TestCase
{
    public function testMergeEnglishPreservesSlovakSlice(): void
    {
        $page = new Page();
        $page->setPath('pages/about.json');
        $page->setFrontMatter([
            'schemaVersion' => 2,
            'defaultLocale' => 'sk',
            'localizedContent' => [
                'sk' => ['title' => 'SK title', 'body' => 'SK body', 'seo' => ['title' => '', 'description' => '', 'canonical' => '', 'ogImage' => '', 'noIndex' => false]],
            ],
            'localeStatus' => ['sk' => 'published'],
            'slug' => 'about',
            'status' => 'published',
        ]);
        $page->setContent('SK body');

        $writer = new LocalizedContentWriter(new LocalizedContentNormalizer($this->settingsMock()));
        $writer->applyLocalePayload($page, [
            'locale' => 'en',
            'title' => 'EN title',
            'content' => 'EN body',
            'status' => 'draft',
            'seoTitle' => 'EN SEO',
        ], 'about');

        $frontMatter = $page->getFrontMatter();
        $this->assertSame(2, $frontMatter['schemaVersion']);
        $this->assertSame('SK title', $frontMatter['localizedContent']['sk']['title']);
        $this->assertSame('EN title', $frontMatter['localizedContent']['en']['title']);
        $this->assertSame('draft', $frontMatter['localeStatus']['en']);
        $this->assertSame('SK title', $page->getTitle());
        $this->assertSame('SK body', $page->getContent());
    }

    public function testFirstLocaleWriteToNonDefaultLocaleSeedsDefaultSlice(): void
    {
        $page = new Page();
        $page->setPath('pages/new-en.json');
        $page->setFrontMatter(['slug' => 'new-en', 'status' => 'draft']);

        $writer = new LocalizedContentWriter(new LocalizedContentNormalizer($this->settingsMock()));
        $writer->applyLocalePayload($page, [
            'locale' => 'en',
            'title' => 'English title',
            'content' => 'English body',
            'status' => 'published',
        ], 'new-en');

        $frontMatter = $page->getFrontMatter();
        $this->assertSame('English title', $frontMatter['localizedContent']['sk']['title']);
        $this->assertSame('English title', $frontMatter['localizedContent']['en']['title']);
        $this->assertSame('published', $frontMatter['localeStatus']['sk']);
        $this->assertSame('English title', $page->getTitle());
        $this->assertSame('published', $page->getStatus());
    }

    public function testLegacyDocumentUpgradesToSchemaV2OnLocaleWrite(): void
    {
        $page = new Page();
        $page->setPath('pages/legacy.md');
        $page->setFrontMatter([
            'title' => 'Legacy',
            'slug' => 'legacy',
            'status' => 'draft',
        ]);
        $page->setContent('# Legacy');

        $writer = new LocalizedContentWriter(new LocalizedContentNormalizer($this->settingsMock()));
        $writer->applyLocalePayload($page, [
            'locale' => 'sk',
            'title' => 'Legacy SK',
            'content' => '# Legacy SK',
            'status' => 'draft',
        ], 'legacy');

        $frontMatter = $page->getFrontMatter();
        $this->assertSame(2, $frontMatter['schemaVersion']);
        $this->assertSame('Legacy SK', $frontMatter['localizedContent']['sk']['title']);
    }

    public function testUpgradeLegacyToSchemaV2PersistsCanonicalSlices(): void
    {
        $page = new Page();
        $page->setPath('pages/about.md');
        $page->setFrontMatter([
            'title' => 'About',
            'slug' => 'about',
            'status' => 'published',
            'seoTitle' => 'About SEO',
        ]);
        $page->setContent('# About us');

        $writer = new LocalizedContentWriter(new LocalizedContentNormalizer($this->settingsMock()));
        $writer->upgradeLegacyToSchemaV2($page, 'sk');

        $frontMatter = $page->getFrontMatter();
        $this->assertSame(2, $frontMatter['schemaVersion']);
        $this->assertSame('sk', $frontMatter['defaultLocale']);
        $this->assertSame('About', $frontMatter['localizedContent']['sk']['title']);
        $this->assertSame('# About us', $frontMatter['localizedContent']['sk']['body']);
        $this->assertSame('published', $frontMatter['localeStatus']['sk']);
        $this->assertSame('About', $page->getTitle());
    }

    public function testHydrateFlatFieldsFromCanonicalRepairsMissingTopLevelTitle(): void
    {
        $page = new Page();
        $page->setPath('pages/broken.json');
        $page->setFrontMatter([
            'schemaVersion' => 2,
            'defaultLocale' => 'sk',
            'slug' => 'broken',
            'status' => 'draft',
            'localizedContent' => [
                'en' => [
                    'title' => 'Recovered title',
                    'body' => 'Recovered body',
                    'seo' => ['title' => '', 'description' => '', 'canonical' => '', 'ogImage' => '', 'noIndex' => false],
                ],
            ],
            'localeStatus' => ['en' => 'published'],
        ]);

        $writer = new LocalizedContentWriter(new LocalizedContentNormalizer($this->settingsMock()));
        $writer->hydrateFlatFieldsFromCanonical($page);

        $this->assertSame('Recovered title', $page->getTitle());
        $this->assertSame('Recovered body', $page->getContent());
        $this->assertSame('published', $page->getStatus());
    }

    public function testHydrateFlatFieldsFromCanonicalRepairsMissingSlugFromPath(): void
    {
        $page = new Page();
        $page->setPath('pages/recovered.json');
        $page->setFrontMatter([
            'schemaVersion' => 2,
            'defaultLocale' => 'sk',
            'status' => 'draft',
            'localizedContent' => [
                'sk' => [
                    'title' => 'Recovered title',
                    'body' => 'Recovered body',
                    'seo' => ['title' => '', 'description' => '', 'canonical' => '', 'ogImage' => '', 'noIndex' => false],
                ],
            ],
            'localeStatus' => ['sk' => 'draft'],
        ]);

        $writer = new LocalizedContentWriter(new LocalizedContentNormalizer($this->settingsMock()));
        $writer->hydrateFlatFieldsFromCanonical($page);

        $this->assertSame('recovered', $page->getSlug());
        $this->assertSame('Recovered title', $page->getTitle());
    }

    public function testHydrateDoesNotClobberExistingFlatContentOrSeoWhenSliceEmpty(): void
    {
        $page = new Page();
        $page->setPath('pages/live-article.json');
        $page->setFrontMatter([
            'schemaVersion' => 2,
            'defaultLocale' => 'sk',
            'slug' => 'live-article',
            'title' => 'Flat title',
            'seoTitle' => 'Flat SEO title',
            'seoDescription' => 'Flat SEO description',
            'seoImage' => '/media/hero.jpg',
            'localizedContent' => [
                'sk' => [
                    'title' => '',
                    'body' => '',
                    'seo' => ['title' => '', 'description' => '', 'canonical' => '', 'ogImage' => '', 'noIndex' => false],
                ],
            ],
            'localeStatus' => ['sk' => 'published'],
        ]);
        $page->setContent('# Existing body');

        $writer = new LocalizedContentWriter(new LocalizedContentNormalizer($this->settingsMock()));
        $writer->hydrateFlatFieldsFromCanonical($page);

        $frontMatter = $page->getFrontMatter();
        $this->assertSame('Flat title', $page->getTitle());
        $this->assertSame('# Existing body', $page->getContent());
        $this->assertSame('Flat SEO title', $frontMatter['seoTitle']);
        $this->assertSame('Flat SEO description', $frontMatter['seoDescription']);
        $this->assertSame('/media/hero.jpg', $frontMatter['seoImage']);
    }

    public function testHydrateStripsEmbeddedMetadataLeakFromFlatAndLocaleSlice(): void
    {
        $leak = <<<'MD'
# Article

Body text.
seo:
  title: beta38
  description: leaked
localeStatus:
  sk: published
MD;

        $page = new Page();
        $page->setPath('pages/leaked.json');
        $page->setFrontMatter([
            'schemaVersion' => 2,
            'defaultLocale' => 'sk',
            'slug' => 'leaked',
            'title' => 'Article',
            'localizedContent' => [
                'sk' => [
                    'title' => 'Article',
                    'body' => $leak,
                    'seo' => ['title' => '', 'description' => '', 'canonical' => '', 'ogImage' => '', 'noIndex' => false],
                ],
            ],
            'localeStatus' => ['sk' => 'published'],
        ]);
        $page->setContent($leak);

        $writer = new LocalizedContentWriter(new LocalizedContentNormalizer($this->settingsMock()));
        $changed = $writer->hydrateFlatFieldsFromCanonical($page);

        $this->assertTrue($changed);
        $this->assertStringContainsString('Body text.', $page->getContent());
        $this->assertStringNotContainsString('localeStatus:', $page->getContent());
        $this->assertStringNotContainsString('localeStatus:', (string) ($page->getFrontMatter()['localizedContent']['sk']['body'] ?? ''));
    }

    public function testApplyBulkStatusSyncsEveryLocaleStatus(): void
    {
        $page = new Page();
        $page->setPath('pages/bulk-status.json');
        $page->setFrontMatter([
            'schemaVersion' => 2,
            'defaultLocale' => 'sk',
            'slug' => 'bulk-status',
            'title' => 'Bulk',
            'status' => 'draft',
            'localizedContent' => [
                'sk' => [
                    'title' => 'SK title',
                    'body' => 'SK body',
                    'seo' => ['title' => '', 'description' => '', 'canonical' => '', 'ogImage' => '', 'noIndex' => false],
                ],
                'en' => [
                    'title' => 'EN title',
                    'body' => 'EN body',
                    'seo' => ['title' => '', 'description' => '', 'canonical' => '', 'ogImage' => '', 'noIndex' => false],
                ],
            ],
            'localeStatus' => ['sk' => 'draft', 'en' => 'draft'],
        ]);
        $page->setContent('SK body');
        $page->setStatus('draft');

        $writer = new LocalizedContentWriter(new LocalizedContentNormalizer($this->settingsMock()));
        $writer->applyBulkStatus($page, 'published');

        $this->assertSame('published', $page->getStatus());
        $frontMatter = $page->getFrontMatter();
        $this->assertSame('published', $frontMatter['localeStatus']['sk']);
        $this->assertSame('published', $frontMatter['localeStatus']['en']);
        $this->assertSame('Bulk', $page->getTitle());
        $this->assertSame('SK body', $page->getContent());
    }

    private function settingsMock(): SettingsRepositoryInterface
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(function (string $key, mixed $default = null): mixed {
            return $key === 'general.language' ? 'sk' : $default;
        });
        $settings->method('group')->willReturn([]);

        return $settings;
    }
}
