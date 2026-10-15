<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Services\ContentMediaReferenceExtractor;
use PHPUnit\Framework\TestCase;

final class ContentMediaReferenceExtractorTest extends TestCase
{
    private ContentMediaReferenceExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new ContentMediaReferenceExtractor();
    }

    public function testExtractsStorageUrlsFromMarkdown(): void
    {
        $text = '![Hero](/storage/app/content/media/hero.jpg) and ![x](/storage/app/content/media/nested/a.png)';
        $paths = $this->extractor->extractFromText($text);
        sort($paths);
        $this->assertSame(['media/hero.jpg', 'media/nested/a.png'], $paths);
    }

    public function testFeaturedImagePathNormalization(): void
    {
        $path = $this->extractor->toContentRelativePath('/storage/app/content/media/image_20261012_abcd.jpg');
        $this->assertSame('media/image_20261012_abcd.jpg', $path);
    }
}
