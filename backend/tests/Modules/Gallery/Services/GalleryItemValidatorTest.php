<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Gallery\Services;

use PaginiumCMS\Modules\Gallery\Models\GalleryItem;
use PaginiumCMS\Modules\Gallery\Services\GalleryItemValidator;
use PHPUnit\Framework\TestCase;

final class GalleryItemValidatorTest extends TestCase
{
    private GalleryItemValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new GalleryItemValidator();
    }

    public function testValidPayload(): void
    {
        $this->assertNull($this->validator->validate([
            'title' => 'Dashboard',
            'description' => 'Overview',
            'mediaPath' => '/storage/media/dashboard.webp',
            'featureTag' => 'analytics',
            'linkUrl' => 'https://example.com/docs',
            'status' => GalleryItem::STATUS_PUBLISHED,
        ]));
    }

    public function testRejectsInvalidMediaPath(): void
    {
        $this->assertNotNull($this->validator->validate([
            'title' => 'Bad',
            'mediaPath' => '../secret.png',
        ]));
    }

    public function testRejectsMissingTitle(): void
    {
        $this->assertNotNull($this->validator->validate([
            'title' => '',
            'mediaPath' => '/storage/media/ok.png',
        ]));
    }
}
