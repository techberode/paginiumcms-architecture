<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\ContentSlug;
use PHPUnit\Framework\TestCase;

final class ContentSlugTest extends TestCase
{
    public function testSlugifyTitleStripsDiacritics(): void
    {
        $this->assertSame('paginiumcms-buducnost', ContentSlug::slugifyTitle('PaginiumCMS | Budúcnosť'));
    }

    public function testSlugFromStoragePath(): void
    {
        $this->assertSame('hello-post', ContentSlug::slugFromStoragePath('blog/hello-post.json'));
    }

    public function testResolveSlugPrefersExistingValue(): void
    {
        $this->assertSame('existing', ContentSlug::resolveSlug('existing', 'Title', 'blog/other.json'));
    }

    public function testResolveSlugFallsBackToPathBasename(): void
    {
        $this->assertSame('from-path', ContentSlug::resolveSlug('', '', 'blog/from-path.json'));
    }

    public function testResolveSlugFallsBackToTitle(): void
    {
        $this->assertSame('my-title', ContentSlug::resolveSlug('', 'My Title', 'blog/.json'));
    }

    public function testResolveSlugGeneratesDraftWhenEverythingEmpty(): void
    {
        $slug = ContentSlug::resolveSlug('', '', 'blog/.json');
        $this->assertMatchesRegularExpression('/^draft-[a-f0-9]{8}$/', $slug);
    }
}
