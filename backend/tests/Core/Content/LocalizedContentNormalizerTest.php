<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\LocalizedContentApplicator;
use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\LocaleResolution;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class LocalizedContentNormalizerTest extends TestCase
{
    public function testLegacyDocumentNormalizesToSingleLocale(): void
    {
        $page = new Page();
        $page->setPath('pages/about.md');
        $page->setFrontMatter([
            'title' => 'O nás',
            'status' => 'published',
            'seoTitle' => 'SEO title',
        ]);
        $page->setContent('# Body');

        $normalizer = new LocalizedContentNormalizer($this->settingsMock());
        $canonical = $normalizer->normalize($page);

        $this->assertSame(1, $canonical['schemaVersion']);
        $this->assertSame('sk', $canonical['defaultLocale']);
        $this->assertSame('O nás', $canonical['localizedContent']['sk']['title']);
        $this->assertSame('# Body', $canonical['localizedContent']['sk']['body']);
        $this->assertSame('published', $canonical['localeStatus']['sk']);
    }

    public function testSchemaV2UsesLocalizedSlices(): void
    {
        $page = new Page();
        $page->setPath('pages/about.json');
        $page->setFrontMatter([
            'schemaVersion' => 2,
            'defaultLocale' => 'sk',
            'localizedContent' => [
                'sk' => ['title' => 'SK', 'body' => 'SK body', 'seo' => ['title' => 'SK SEO']],
                'en' => ['title' => 'EN', 'body' => 'EN body', 'seo' => ['title' => 'EN SEO']],
            ],
            'localeStatus' => ['sk' => 'published', 'en' => 'draft'],
            'status' => 'published',
        ]);
        $page->setContent('ignored');

        $normalizer = new LocalizedContentNormalizer($this->settingsMock());
        $canonical = $normalizer->normalize($page);

        $this->assertSame(2, $canonical['schemaVersion']);
        $this->assertSame('EN', $canonical['localizedContent']['en']['title']);
        $this->assertSame('draft', $canonical['localeStatus']['en']);
    }

    public function testApplicatorOverridesPayloadFields(): void
    {
        $page = new Page();
        $page->setFrontMatter([
            'schemaVersion' => 2,
            'defaultLocale' => 'sk',
            'localizedContent' => [
                'sk' => ['title' => 'SK', 'body' => 'SK body'],
                'en' => ['title' => 'EN', 'body' => 'EN body', 'seo' => ['title' => 'EN SEO', 'description' => 'desc']],
            ],
            'localeStatus' => ['sk' => 'published', 'en' => 'published'],
        ]);
        $page->setContent('legacy');

        $canonical = (new LocalizedContentNormalizer($this->settingsMock()))->normalize($page);
        $payload = [
            'title' => 'Legacy',
            'content' => 'Legacy body',
            'seoTitle' => '',
            'seoDescription' => '',
        ];

        $applied = (new LocalizedContentApplicator())->apply(
            $payload,
            $canonical,
            new LocaleResolution('en', 'en', false, ['sk', 'en'])
        );

        $this->assertSame('EN', $applied['title']);
        $this->assertSame('EN body', $applied['content']);
        $this->assertSame('EN SEO', $applied['seoTitle']);
        $this->assertSame('en', $applied['_locale']['resolved']);
        $this->assertFalse($applied['_locale']['fallback']);
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
