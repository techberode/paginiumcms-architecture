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

        return MediaFormats::contentMatchesMime($bytes, $mimeType);
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

}
