<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Services\ContentMediaIntegrityService;
use PaginiumCMS\Core\FlatFile\Services\ContentMediaReferenceExtractor;
use PHPUnit\Framework\TestCase;

final class ContentMediaIntegrityServiceTest extends TestCase
{
    public function testDetectsMissingFeaturedImage(): void
    {
        $article = (new Article())
            ->setPath('blog/post.md')
            ->setFrontMatter([
                'title' => 'Post',
                'featuredImage' => '/storage/app/content/media/missing.jpg',
            ])
            ->setContent('');

        $repository = $this->createMock(ContentRepositoryInterface::class);
        $repository->method('findAllPages')->willReturn([]);
        $repository->method('findAllArticles')->willReturn([$article]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturnCallback(
            static fn (string $path): bool => $path !== 'media/missing.jpg'
        );

        $service = new ContentMediaIntegrityService(
            $repository,
            $reader,
            new ContentMediaReferenceExtractor(),
        );

        $report = $service->scan();
        $this->assertFalse($report['healthy']);
        $this->assertSame(1, $report['missingTotal']);
        $this->assertSame('media/missing.jpg', $report['missing'][0]['mediaPath']);
    }
}
