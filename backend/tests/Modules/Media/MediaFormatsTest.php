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
}
