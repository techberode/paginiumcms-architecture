<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Core\Layout\Services\MediaGalleryRenderer;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class MediaGalleryRendererTest extends TestCase
{
    public function testParseIdsSplitsPipeAndComma(): void
    {
        $ids = MediaGalleryRenderer::parseIds(' media/a.jpg | media/b.png,media/c.webp ');
        $this->assertSame(['media/a.jpg', 'media/b.png', 'media/c.webp'], $ids);
    }

    public function testParseIdsRejectsTraversal(): void
    {
        $this->assertSame([], MediaGalleryRenderer::parseIds('../etc/passwd'));
        $this->assertSame([], MediaGalleryRenderer::parseIds('/storage/evil.jpg'));
    }

    public function testRenderEmptyWhenNoMedia(): void
    {
        $html = MediaGalleryRenderer::render(['title' => 'Work', 'ids' => 'media/x.jpg'], null);
        $this->assertStringContainsString('pg-media-gallery-empty', $html);
        $this->assertStringContainsString('data-island="media-gallery"', $html);
    }

    public function testRenderGridForResolvedImages(): void
    {
        $file = new MediaFile();
        $file->setPath('media/hero.jpg');
        $file->setUrl('/storage/media/hero.jpg');
        $file->setMimeType('image/jpeg');
        $file->setAltText('Hero shot');
        $file->setTitle('Studio');

        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->method('findByPath')->willReturnCallback(static function (string $path) use ($file): ?MediaFile {
            return $path === 'media/hero.jpg' ? $file : null;
        });

        $html = MediaGalleryRenderer::render(
            [
                'title' => 'Portfolio',
                'ids' => 'media/hero.jpg|missing.png',
                'columns' => '3',
                'layout' => 'masonry',
            ],
            $repo
        );

        $this->assertStringContainsString('pg-media-gallery-grid--masonry', $html);
        $this->assertStringContainsString('pg-media-gallery-cols-3', $html);
        $this->assertStringContainsString('/storage/media/hero.jpg', $html);
        $this->assertStringContainsString('data-media-path="media/hero.jpg"', $html);
        $this->assertStringNotContainsString('missing.png', $html);
    }

    public function testRenderVideoItemMarkup(): void
    {
        $file = new MediaFile();
        $file->setPath('media/clip.mp4');
        $file->setUrl('/storage/media/clip.mp4');
        $file->setMimeType('video/mp4');
        $file->setAltText('Clip');
        $file->setTitle('Showreel');

        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->method('findByPath')->willReturn($file);

        $html = MediaGalleryRenderer::render(['ids' => 'media/clip.mp4'], $repo);

        $this->assertStringContainsString('data-media-type="video"', $html);
        $this->assertStringContainsString('pg-media-gallery-video', $html);
        $this->assertStringContainsString('/storage/media/clip.mp4', $html);
        $this->assertStringNotContainsString('pg-media-gallery-image', $html);
    }
}
