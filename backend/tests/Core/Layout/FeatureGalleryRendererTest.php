<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\FeatureGalleryRenderer;
use PaginiumCMS\Modules\Gallery\Models\GalleryItem;
use PHPUnit\Framework\TestCase;

final class FeatureGalleryRendererTest extends TestCase
{
    public function testRendersPublishedDamItemsAndPinsTag(): void
    {
        $web = GalleryItem::fromArray([
            'title' => 'Analytics',
            'description' => 'Dashboard',
            'mediaPath' => '/storage/app/content/media/analytics.png',
            'featureTag' => 'web',
            'status' => GalleryItem::STATUS_PUBLISHED,
        ], 'gallery_1');
        $print = GalleryItem::fromArray([
            'title' => 'Poster',
            'mediaPath' => '/storage/app/content/media/poster.jpg',
            'featureTag' => 'print',
            'status' => GalleryItem::STATUS_PUBLISHED,
        ], 'gallery_2');

        $html = FeatureGalleryRenderer::render(
            ['title' => 'Selected work', 'tag' => 'web'],
            [$web, $print]
        );

        $this->assertStringContainsString('pg-feature-gallery', $html);
        $this->assertStringContainsString('data-tag="web"', $html);
        $this->assertStringContainsString('data-title="Selected work"', $html);
        $this->assertStringContainsString('Selected work', $html);
        $this->assertStringContainsString('Analytics', $html);
        $this->assertStringContainsString('/storage/app/content/media/analytics.png', $html);
        $this->assertStringNotContainsString('Poster', $html);
        $this->assertStringNotContainsString('<figure', $html);
    }

    public function testDropsExternalMediaAndEmptyStoreShowsHint(): void
    {
        $evil = GalleryItem::fromArray([
            'title' => 'Evil',
            'mediaPath' => 'https://evil.example/x.jpg',
            'status' => GalleryItem::STATUS_PUBLISHED,
        ], 'gallery_x');

        $dropped = FeatureGalleryRenderer::render([], [$evil]);
        $this->assertStringNotContainsString('evil.example', $dropped);
        $this->assertStringContainsString('pg-feature-gallery-empty', $dropped);

        $empty = FeatureGalleryRenderer::render(['title' => 'Work'], []);
        $this->assertStringContainsString('data-title="Work"', $empty);
        $this->assertStringContainsString('pg-feature-gallery-empty', $empty);
    }

    public function testEscapesTitleAndAllowsSafeLinks(): void
    {
        $item = GalleryItem::fromArray([
            'title' => '<script>x</script>',
            'mediaPath' => '/api/media/file/shot.jpg',
            'linkUrl' => '/portfolio',
            'status' => GalleryItem::STATUS_PUBLISHED,
        ], 'gallery_3');

        $html = FeatureGalleryRenderer::render(
            ['title' => 'A & B'],
            [$item]
        );

        $this->assertStringContainsString('A &amp; B', $html);
        $this->assertStringNotContainsString('<script>x</script>', $html);
        $this->assertStringContainsString('href="/portfolio"', $html);
    }
}
