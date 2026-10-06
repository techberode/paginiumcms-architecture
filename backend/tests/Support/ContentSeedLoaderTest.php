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

    public function testLoadsExperiencePhaseCReferenceSeeds(): void
    {
        $agency = ContentSeedLoader::load('reference-agency.en.md');
        $this->assertStringContainsString('slug: reference-agency', $agency);
        $this->assertStringContainsString('[feature-gallery', $agency);

        $saas = ContentSeedLoader::load('reference-saas.en.md');
        $this->assertStringContainsString('[pricing-table', $saas);

        $local = ContentSeedLoader::load('reference-local-craft.en.md');
        $this->assertStringContainsString('[gallery-carousel', $local);
    }
}
