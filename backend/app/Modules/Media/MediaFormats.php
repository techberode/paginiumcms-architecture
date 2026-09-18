<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

/**
 * Single source of truth for allowed media MIME types, extensions, and content validation.
 */
final class MediaFormats
{
    /**
     * @var array<string, array{extensions: list<string>, previewable: bool}>
     */
    private const FORMATS = [
        'image/jpeg' => ['extensions' => ['jpg', 'jpeg'], 'previewable' => true],
        'image/png' => ['extensions' => ['png'], 'previewable' => true],
        'image/gif' => ['extensions' => ['gif'], 'previewable' => true],
        'image/webp' => ['extensions' => ['webp'], 'previewable' => true],
        'image/svg+xml' => ['extensions' => ['svg'], 'previewable' => true],
        'application/pdf' => ['extensions' => ['pdf'], 'previewable' => false],
        'text/plain' => ['extensions' => ['txt'], 'previewable' => false],
        'text/markdown' => ['extensions' => ['md'], 'previewable' => false],
        'application/vnd.oasis.opendocument.text' => ['extensions' => ['odt'], 'previewable' => false],
        'application/vnd.oasis.opendocument.spreadsheet' => ['extensions' => ['ods'], 'previewable' => false],
        'application/vnd.oasis.opendocument.presentation' => ['extensions' => ['odp'], 'previewable' => false],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['extensions' => ['docx'], 'previewable' => false],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['extensions' => ['xlsx'], 'previewable' => false],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['extensions' => ['pptx'], 'previewable' => false],
        'video/mp4' => ['extensions' => ['mp4'], 'previewable' => false],
        'video/webm' => ['extensions' => ['webm'], 'previewable' => false],
    ];

    /**
     * @return list<string>
     */
    public static function defaultMimeTypes(): array
    {
        return array_keys(self::FORMATS);
    }

    /**
     * @param list<string> $allowedMimeTypes
     *
     * @return array{
     *     mimeTypes: list<string>,
     *     extensions: list<string>,
     *     accept: string,
     *     previewableMimeTypes: list<string>
     * }
     */
    public static function toApiPayload(array $allowedMimeTypes): array
    {
        $extensions = [];
        $previewable = [];

        foreach ($allowedMimeTypes as $mimeType) {
            if (!isset(self::FORMATS[$mimeType])) {
                continue;
            }

            foreach (self::FORMATS[$mimeType]['extensions'] as $extension) {
                $extensions[] = $extension;
            }

            if (self::FORMATS[$mimeType]['previewable']) {
                $previewable[] = $mimeType;
            }
        }

        sort($extensions);
        sort($previewable);

        return [
            'mimeTypes' => $allowedMimeTypes,
            'extensions' => array_values(array_unique($extensions)),
            'accept' => implode(',', $allowedMimeTypes),
            'previewableMimeTypes' => array_values(array_unique($previewable)),
        ];
    }

    public static function isKnownMime(string $mimeType): bool
    {
        return isset(self::FORMATS[strtolower(trim($mimeType))]);
    }

    /**
     * Infer MIME from filename when the client sends a generic type (common for Office/text uploads).
     */
    public static function guessMimeFromExtension(string $filename): ?string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === '') {
            return null;
        }

        foreach (self::FORMATS as $mimeType => $meta) {
            if (in_array($extension, $meta['extensions'], true)) {
                return $mimeType;
            }
        }

        return null;
    }

    /**
     * Replace generic/unknown declared MIME with an extension-based guess when possible.
     */
    public static function coalesceDeclaredMime(string $filename, string $declaredMime): string
    {
        $declaredMime = strtolower(trim($declaredMime));
        $inferred = self::guessMimeFromExtension($filename);

        if ($inferred === null) {
            return $declaredMime;
        }

        if ($declaredMime === '' || !self::isKnownMime($declaredMime) || self::isGenericDeclaredMime($declaredMime)) {
            return $inferred;
        }

        return $declaredMime;
    }

    private static function isGenericDeclaredMime(string $mimeType): bool
    {
        return in_array($mimeType, [
            'application/octet-stream',
            'binary/octet-stream',
            'application/x-msdownload',
            'application/force-download',
        ], true);
    }

    public static function isImageMime(string $mimeType): bool
    {
        return str_starts_with(strtolower(trim($mimeType)), 'image/') && self::isKnownMime($mimeType);
    }

    public static function isVideoMime(string $mimeType): bool
    {
        return str_starts_with(strtolower(trim($mimeType)), 'video/') && self::isKnownMime($mimeType);
    }

    /**
     * @return list<string>
     */
    public static function defaultDocumentMimeTypes(): array
    {
        return array_values(array_filter(
            self::defaultMimeTypes(),
            static fn (string $mime): bool => self::isDocumentMime($mime)
        ));
    }

    public static function isDocumentMime(string $mimeType): bool
    {
        $mimeType = strtolower(trim($mimeType));

        if (!self::isKnownMime($mimeType)) {
            return false;
        }

        return self::isTextEditableMime($mimeType)
            || $mimeType === 'application/pdf'
            || self::isOpenDocumentMime($mimeType)
            || self::isOfficeOpenXmlMime($mimeType);
    }

    public static function isTextEditableMime(string $mimeType): bool
    {
        $mimeType = strtolower(trim($mimeType));

        return in_array($mimeType, ['text/plain', 'text/markdown'], true);
    }

    public static function isAdminPdfPreviewMime(string $mimeType): bool
    {
        return strtolower(trim($mimeType)) === 'application/pdf';
    }

    /**
     * @return list<string>
     */
    public static function defaultVideoMimeTypes(): array
    {
        return array_values(array_filter(
            self::defaultMimeTypes(),
            static fn (string $mime): bool => self::isVideoMime($mime)
        ));
    }

    public static function isPreviewableMime(string $mimeType): bool
    {
        $mimeType = strtolower(trim($mimeType));

        return isset(self::FORMATS[$mimeType]) && self::FORMATS[$mimeType]['previewable'];
    }

    /**
     * @param list<string> $allowedMimeTypes
     */
    public static function buildAcceptHeader(array $allowedMimeTypes): string
    {
        return implode(',', array_values(array_filter(
            $allowedMimeTypes,
            static fn (string $mimeType): bool => self::isKnownMime($mimeType)
        )));
    }

    /**
     * @param list<string> $allowedMimeTypes
     */
    public static function validate(
        string $filename,
        string $bytes,
        string $declaredMime,
        array $allowedMimeTypes,
        bool $verifyContent = true
    ): string {
        $declaredMime = strtolower(trim($declaredMime));

        if (!in_array($declaredMime, $allowedMimeTypes, true)) {
            throw new FlatFileException('Nepodporovaný typ súboru: ' . $declaredMime);
        }

        if (!self::isKnownMime($declaredMime)) {
            throw new FlatFileException('Neznámy formát: ' . $declaredMime);
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === '' || !self::extensionMatchesMime($extension, $declaredMime)) {
            throw new FlatFileException('Prípona súboru nezodpovedá povolenému typu');
        }

        if ($verifyContent && !self::contentMatchesMime($bytes, $declaredMime)) {
            throw new FlatFileException('Obsah súboru nezodpovedá deklarovanému typu');
        }

        if ($verifyContent && self::isVideoMime($declaredMime)) {
            self::assertNoEmbeddedHtmlMarkers($bytes);
        }

        return $declaredMime;
    }

    public static function contentMatchesMime(string $bytes, string $mimeType): bool
    {
        $mimeType = strtolower(trim($mimeType));

        if ($bytes === '' || !self::isKnownMime($mimeType)) {
            return false;
        }

        return self::matchContentToMime($bytes, $mimeType);
    }

    private static function extensionMatchesMime(string $extension, string $mimeType): bool
    {
        return in_array($extension, self::FORMATS[$mimeType]['extensions'], true);
    }

    private static function matchContentToMime(string $bytes, string $mimeType): bool
    {
        return match ($mimeType) {
            'image/jpeg' => str_starts_with($bytes, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($bytes, "\x89PNG\r\n\x1a\n"),
            'image/gif' => str_starts_with($bytes, 'GIF87a') || str_starts_with($bytes, 'GIF89a'),
            'image/webp' => strlen($bytes) >= 12
                && str_starts_with($bytes, 'RIFF')
                && substr($bytes, 8, 4) === 'WEBP',
            'image/svg+xml' => self::looksLikeSvg($bytes),
            'application/pdf' => str_starts_with($bytes, '%PDF-'),
            'text/plain', 'text/markdown' => self::looksLikePlainText($bytes),
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => self::looksLikeZipPackage($bytes),
            'video/mp4' => self::looksLikeMp4($bytes),
            'video/webm' => self::looksLikeWebm($bytes),
            default => false,
        };
    }

    public static function isOpenDocumentMime(string $mimeType): bool
    {
        return str_starts_with(strtolower(trim($mimeType)), 'application/vnd.oasis.opendocument.');
    }

    public static function isOfficeOpenXmlMime(string $mimeType): bool
    {
        return str_starts_with(
            strtolower(trim($mimeType)),
            'application/vnd.openxmlformats-officedocument.'
        );
    }

    private static function looksLikePlainText(string $bytes): bool
    {
        if ($bytes === '') {
            return false;
        }

        if (str_contains($bytes, "\0")) {
            return false;
        }

        $sample = strtolower(substr($bytes, 0, 65536));
        foreach (['<script', '<?php', 'javascript:'] as $marker) {
            if (str_contains($sample, $marker)) {
                return false;
            }
        }

        return true;
    }

    private static function looksLikeZipPackage(string $bytes): bool
    {
        if (!str_starts_with($bytes, "PK\x03\x04")) {
            return false;
        }

        return str_contains($bytes, '[Content_Types].xml');
    }

    private static function looksLikeMp4(string $bytes): bool
    {
        if (strlen($bytes) < 12) {
            return false;
        }

        return substr($bytes, 4, 4) === 'ftyp';
    }

    private static function looksLikeWebm(string $bytes): bool
    {
        return str_starts_with($bytes, "\x1A\x45\xDF\xA3");
    }

    private static function assertNoEmbeddedHtmlMarkers(string $bytes): void
    {
        $sample = strtolower(substr($bytes, 0, 65536));
        foreach (['<script', '<html', '<?php', 'javascript:'] as $marker) {
            if (str_contains($sample, $marker)) {
                throw new FlatFileException('Video súbor obsahuje podozrivé HTML/script značky');
            }
        }
    }

    private static function looksLikeSvg(string $bytes): bool
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
