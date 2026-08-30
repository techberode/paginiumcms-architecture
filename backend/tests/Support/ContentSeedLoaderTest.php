<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Support\ContentSeedLoader;
use PHPUnit\Framework\TestCase;

final class ContentSeedLoaderTest extends TestCase
{
    public function testLoadsPaginiumLandingSeed(): void
    {
        $markdown = ContentSeedLoader::load('paginium-cms-landing.sk.md');

        $this->assertStringContainsString('slug: paginium-cms', $markdown);
        $this->assertStringContainsString('[showcase-hero', $markdown);
    }
}
