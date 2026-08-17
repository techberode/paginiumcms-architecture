<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Support\TestArtifactNaming;
use PHPUnit\Framework\TestCase;

final class TestArtifactNamingTest extends TestCase
{
    public function testUniqueSlugUsesQaPrefix(): void
    {
        $slug = TestArtifactNaming::uniqueSlug('article');

        $this->assertStringStartsWith(TestArtifactNaming::QA_PREFIX, $slug);
        $this->assertTrue(TestArtifactNaming::isTestContentSlug($slug));
    }

    public function testLegacyPhpUnitPrefixesAreDetected(): void
    {
        $this->assertTrue(TestArtifactNaming::isTestContentSlug('seo-test-abc'));
        $this->assertTrue(TestArtifactNaming::isTestContentSlug('bulk-a-123'));
        $this->assertFalse(TestArtifactNaming::isTestContentSlug('about-us'));
        $this->assertFalse(TestArtifactNaming::isTestContentSlug('blog'));
    }
}
