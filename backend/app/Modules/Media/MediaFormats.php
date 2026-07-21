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

    public static function isImageMime(string $mimeType): bool
    {
        return str_starts_with(strtolower(trim($mimeType)), 'image/') && self::isKnownMime($mimeType);
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

        return $declaredMime;
    }

    private static function extensionMatchesMime(string $extension, string $mimeType): bool
    {
        return in_array($extension, self::FORMATS[$mimeType]['extensions'], true);
    }

    private static function contentMatchesMime(string $bytes, string $mimeType): bool
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
            'image/svg+xml' => self::looksLikeSvg($bytes),
            'application/pdf' => str_starts_with($bytes, '%PDF-'),
            default => false,
        };
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
