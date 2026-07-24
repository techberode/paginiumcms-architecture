<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Navigation\Services;

use PaginiumCMS\Core\FlatFile\Models\NavigationItem;
use PaginiumCMS\Support\Lang;

/**
 * Validates rich navigation item fields (It.56).
 */
final class NavigationRichFieldValidator
{
    private const MEDIA_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];

    /**
     * @param array<string, mixed> $entry
     */
    public function validateEntry(array $entry): ?string
    {
        $description = trim((string) ($entry['description'] ?? ''));
        if (mb_strlen($description) > NavigationItem::MAX_DESCRIPTION_LENGTH) {
            return Lang::get('description_too_long', [], 'navigation');
        }

        $iconType = NavigationItem::ICON_TYPES[0];
        $rawIconType = strtolower(trim((string) ($entry['iconType'] ?? 'none')));
        if ($rawIconType !== '' && !in_array($rawIconType, NavigationItem::ICON_TYPES, true)) {
            return Lang::get('invalid_icon_type', [], 'navigation');
        }
        if (in_array($rawIconType, NavigationItem::ICON_TYPES, true)) {
            $iconType = $rawIconType;
        }

        $iconValue = trim((string) ($entry['iconValue'] ?? ''));
        if ($iconType !== 'none' && $iconValue === '') {
            $legacyIcon = trim((string) ($entry['icon'] ?? ''));
            if ($legacyIcon === '') {
                return Lang::get('icon_value_required', [], 'navigation');
            }
        }

        if ($iconType === 'media') {
            $path = $iconValue !== '' ? $iconValue : trim((string) ($entry['icon'] ?? ''));
            if ($path === '' || !$this->isAllowedMediaPath($path)) {
                return Lang::get('invalid_media_icon', [], 'navigation');
            }
        }

        if ($iconType === 'lucide') {
            $name = $iconValue !== '' ? $iconValue : trim((string) ($entry['icon'] ?? ''));
            if ($name === '' || !preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name)) {
                return Lang::get('invalid_lucide_icon', [], 'navigation');
            }
        }

        $thumbnailSize = strtolower(trim((string) ($entry['thumbnailSize'] ?? 'sm')));
        if ($thumbnailSize !== '' && !in_array($thumbnailSize, NavigationItem::THUMBNAIL_SIZES, true)) {
            return Lang::get('invalid_thumbnail_size', [], 'navigation');
        }

        if (array_key_exists('previewScale', $entry) && $entry['previewScale'] !== null && $entry['previewScale'] !== '') {
            if (!is_numeric($entry['previewScale'])) {
                return Lang::get('invalid_preview_scale', [], 'navigation');
            }
            $scale = (float) $entry['previewScale'];
            if ($scale < 1.0 || $scale > 3.0) {
                return Lang::get('invalid_preview_scale', [], 'navigation');
            }
        }

        return null;
    }

    private function isAllowedMediaPath(string $path): bool
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            return false;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }

        if (!str_starts_with($path, '/')) {
            return false;
        }

        $pathPart = parse_url($path, PHP_URL_PATH);
        if (!is_string($pathPart) || $pathPart === '') {
            $pathPart = $path;
        }

        $extension = strtolower(pathinfo($pathPart, PATHINFO_EXTENSION));

        return in_array($extension, self::MEDIA_EXTENSIONS, true);
    }
}
