<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Upload;

use PaginiumCMS\Modules\Media\MediaFormats;

/**
 * Shared magic-byte sniffing for binary uploads (It.78).
 */
final class UploadMagicByteInspector
{
    public function matchesMime(string $bytes, string $mimeType): bool
    {
        $mimeType = strtolower(trim($mimeType));

        if ($mimeType === 'application/zip' || $mimeType === 'application/x-zip-compressed') {
            return $this->looksLikeZip($bytes);
        }

        if (!MediaFormats::isKnownMime($mimeType)) {
            return false;
        }

        return $this->contentMatchesKnownMime($bytes, $mimeType);
    }

    public function looksLikeZip(string $bytes): bool
    {
        if ($bytes === '') {
            return false;
        }

        return str_starts_with($bytes, "PK\x03\x04")
            || str_starts_with($bytes, "PK\x05\x06")
            || str_starts_with($bytes, "PK\x07\x08");
    }

    private function contentMatchesKnownMime(string $bytes, string $mimeType): bool
    {
        if ($bytes === '') {
            return false;
        }

        return match ($mimeType) {
            'image/jpeg' => str_starts_with($bytes, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($bytes, "\x89PNG\r\n\x1a\n"),
            'image/gif' => str_starts_with($bytes, 'GIF87a') || str_starts_with($bytes, 'GIF89a'),
            'image/webp' => strlen($bytes) >= 12
                && str_starts_with($bytes, 'RIFF')
                && substr($bytes, 8, 4) === 'WEBP',
            'image/svg+xml' => $this->looksLikeSvg($bytes),
            'application/pdf' => str_starts_with($bytes, '%PDF-'),
            default => false,
        };
    }

    private function looksLikeSvg(string $bytes): bool
    {
        $sample = ltrim(substr($bytes, 0, 4096));

        if ($sample === '') {
            return false;
        }

        if (str_starts_with($sample, '<?xml') || str_starts_with($sample, '<svg')) {
            return stripos($sample, '<svg') !== false;
        }

        return false;
    }
}
