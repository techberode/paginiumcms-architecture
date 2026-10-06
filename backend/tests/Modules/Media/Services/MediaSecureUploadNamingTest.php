<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Modules\Media\Services\MediaSecureUploadNaming;
use PHPUnit\Framework\TestCase;

final class MediaSecureUploadNamingTest extends TestCase
{
    public function testSecureImageNamingUsesDateAndShortId(): void
    {
        $name = MediaSecureUploadNaming::build(
            'media_6789abcdef0123',
            'image/jpeg',
            'vacation photo.JPG',
            true
        );

        $this->assertMatchesRegularExpression('/^image_\d{8}_6789abcd\.jpg$/', $name);
    }

    public function testLegacyNamingWhenDisabled(): void
    {
        $name = MediaSecureUploadNaming::build(
            'media_abc',
            'image/png',
            'logo.png',
            false
        );

        $this->assertSame('media_abc_logo.png', $name);
    }

    public function testVideoNaming(): void
    {
        $name = MediaSecureUploadNaming::build(
            'media_12345678',
            'video/webm',
            'clip.webm',
            true
        );

        $this->assertMatchesRegularExpression('/^video_\d{8}_12345678\.webm$/', $name);
    }
}
