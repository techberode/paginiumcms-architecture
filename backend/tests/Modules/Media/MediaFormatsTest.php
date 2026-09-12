<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Media\MediaFormats;
use PHPUnit\Framework\TestCase;

class MediaFormatsTest extends TestCase
{
    private const PNG_BYTES = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function testDefaultMimeTypesIncludeStrictSet(): void
    {
        $types = MediaFormats::defaultMimeTypes();

        $this->assertContains('image/jpeg', $types);
        $this->assertContains('image/png', $types);
        $this->assertContains('application/pdf', $types);
    }

    public function testValidateAcceptsValidPng(): void
    {
        $bytes = base64_decode(self::PNG_BYTES, true);
        $this->assertNotFalse($bytes);

        $mime = MediaFormats::validate('photo.png', $bytes, 'image/png', MediaFormats::defaultMimeTypes());

        $this->assertSame('image/png', $mime);
    }

    public function testValidateRejectsMismatchedContent(): void
    {
        $this->expectException(FlatFileException::class);

        MediaFormats::validate('photo.png', 'not-a-png', 'image/png', MediaFormats::defaultMimeTypes());
    }

    public function testValidateRejectsUnknownMime(): void
    {
        $bytes = base64_decode(self::PNG_BYTES, true);
        $this->assertNotFalse($bytes);

        $this->expectException(FlatFileException::class);

        MediaFormats::validate('photo.png', $bytes, 'image/png', ['image/jpeg']);
    }

    public function testToApiPayloadBuildsAcceptHeader(): void
    {
        $payload = MediaFormats::toApiPayload(['image/png', 'application/pdf']);

        $this->assertSame(['image/png', 'application/pdf'], $payload['mimeTypes']);
        $this->assertContains('png', $payload['extensions']);
        $this->assertContains('pdf', $payload['extensions']);
        $this->assertSame('image/png,application/pdf', $payload['accept']);
        $this->assertSame(['image/png'], $payload['previewableMimeTypes']);
    }

    public function testValidateAcceptsMinimalMp4Header(): void
    {
        $bytes = "\x00\x00\x00\x18ftypisom\x00\x00\x00\x00";
        $mime = MediaFormats::validate('clip.mp4', $bytes, 'video/mp4', ['video/mp4', 'video/webm']);

        $this->assertSame('video/mp4', $mime);
        $this->assertTrue(MediaFormats::isVideoMime($mime));
    }

    public function testValidateAcceptsWebmHeader(): void
    {
        $bytes = "\x1A\x45\xDF\xA3\x01\x00\x00\x00";
        $mime = MediaFormats::validate('clip.webm', $bytes, 'video/webm', ['video/webm']);

        $this->assertSame('video/webm', $mime);
    }

    public function testValidateRejectsPolyglotVideoWithScriptMarker(): void
    {
        $bytes = "\x00\x00\x00\x18ftypisom<script>alert(1)</script>";

        $this->expectException(FlatFileException::class);

        MediaFormats::validate('evil.mp4', $bytes, 'video/mp4', ['video/mp4']);
    }
}
