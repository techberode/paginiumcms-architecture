<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Seo\Services;

use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\Seo\Services\SeoMetaBuilder;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SeoMetaBuilderTest extends TestCase
{
    public function testBuildsPageMetaWithTemplate(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['general', ['siteName' => 'Paginium', 'siteUrl' => 'https://example.com']],
            ['seo', [
                'titleTemplate' => '%title% | %siteName%',
                'defaultDescription' => 'Default desc',
                'defaultImage' => '/storage/og.png',
                'robotsDefault' => 'index,follow',
                'twitterCard' => 'summary_large_image',
            ]],
        ]);

        $page = new Page();
        $page->setSlug('about');
        $page->setFrontMatter([
            'title' => 'About us',
            'slug' => 'about',
            'status' => 'published',
            'description' => 'We build CMS.',
        ]);
        $page->setContent('# About');

        $builder = new SeoMetaBuilder($settings);
        $meta = $builder->buildForContent($page, 'page', 'about');

        $this->assertSame('About us | Paginium', $meta['title']);
        $this->assertSame('We build CMS.', $meta['description']);
        $this->assertSame('https://example.com/about', $meta['canonical']);
        $this->assertSame('website', $meta['openGraph']['type']);
        $this->assertSame('WebPage', $meta['jsonLd']['@type']);
    }

    public function testArticleUsesNoIndexFromFrontMatter(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['general', ['siteName' => 'Paginium', 'siteUrl' => 'https://example.com']],
            ['seo', [
                'titleTemplate' => '%title%',
                'robotsDefault' => 'index,follow',
                'twitterCard' => 'summary',
            ]],
        ]);

        $article = new Article();
        $article->setSlug('secret');
        $article->setFrontMatter([
            'title' => 'Secret',
            'slug' => 'secret',
            'status' => 'published',
            'noIndex' => true,
        ]);
        $article->setContent('Hidden content');

        $builder = new SeoMetaBuilder($settings);
        $meta = $builder->buildForContent($article, 'article', 'secret');

        $this->assertSame('noindex,nofollow', $meta['robots']);
        $this->assertSame('article', $meta['openGraph']['type']);
        $this->assertSame('Article', $meta['jsonLd']['@type']);
        $this->assertSame('https://example.com/blog/secret', $meta['canonical']);
    }
}
