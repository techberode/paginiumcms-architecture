<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Document upload settings (It.96) — toggles and allow-list separate from raster media.
 */
final class MediaDocumentPolicy
{
    public static function isEnabled(SettingsRepositoryInterface $settings): bool
    {
        $value = $settings->group('media')['documentsEnabled'] ?? true;

        return !self::isFalsy($value);
    }

    /**
     * @return list<string>
     */
    public static function allowedMimeTypes(SettingsRepositoryInterface $settings): array
    {
        if (!self::isEnabled($settings)) {
            return [];
        }

        $raw = trim((string) ($settings->group('media')['documentMimeTypes'] ?? ''));
        if ($raw === '') {
            return MediaFormats::defaultDocumentMimeTypes();
        }

        $parsed = array_values(array_filter(
            array_map(static fn (string $part): string => strtolower(trim($part)), explode(',', $raw)),
            static fn (string $mime): bool => $mime !== '' && MediaFormats::isDocumentMime($mime)
        ));

        return $parsed !== [] ? $parsed : MediaFormats::defaultDocumentMimeTypes();
    }

    public static function maxUploadBytes(SettingsRepositoryInterface $settings): int
    {
        $kb = (int) ($settings->group('media')['maxDocumentUploadSizeKb'] ?? 20480);

        return max(64, min(524288, $kb)) * 1024;
    }

    private static function isFalsy(mixed $value): bool
    {
        if (is_bool($value)) {
            return !$value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['', '0', 'false', 'off', 'no'], true);
    }
}
