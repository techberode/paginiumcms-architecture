<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Gallery\Services;

use PaginiumCMS\Modules\Gallery\Models\GalleryItem;
use PaginiumCMS\Support\Lang;

final class GalleryItemValidator
{
    private const MEDIA_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];

    private const MAX_TITLE = 200;
    private const MAX_DESCRIPTION = 2000;
    private const MAX_FEATURE_TAG = 50;
    private const MAX_LINK_URL = 500;

    /**
     * @param array<string, mixed> $payload
     */
    public function validate(array $payload, bool $requireMedia = true): ?string
    {
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            return Lang::get('title_required', [], 'gallery');
        }
        if (mb_strlen($title) > self::MAX_TITLE) {
            return Lang::get('title_too_long', [], 'gallery');
        }

        $description = trim((string) ($payload['description'] ?? ''));
        if (mb_strlen($description) > self::MAX_DESCRIPTION) {
            return Lang::get('description_too_long', [], 'gallery');
        }

        $mediaPath = trim((string) ($payload['mediaPath'] ?? ''));
        if ($requireMedia && $mediaPath === '') {
            return Lang::get('media_required', [], 'gallery');
        }
        if ($mediaPath !== '' && !$this->isAllowedMediaPath($mediaPath)) {
            return Lang::get('invalid_media_path', [], 'gallery');
        }

        if (array_key_exists('featureTag', $payload)) {
            $tag = trim((string) $payload['featureTag']);
            if ($tag !== '' && (mb_strlen($tag) > self::MAX_FEATURE_TAG || !preg_match('/^[a-z0-9_-]+$/i', $tag))) {
                return Lang::get('invalid_feature_tag', [], 'gallery');
            }
        }

        if (array_key_exists('linkUrl', $payload)) {
            $url = trim((string) $payload['linkUrl']);
            if ($url !== '' && (mb_strlen($url) > self::MAX_LINK_URL || filter_var($url, FILTER_VALIDATE_URL) === false)) {
                return Lang::get('invalid_link_url', [], 'gallery');
            }
        }

        if (array_key_exists('status', $payload)) {
            $status = (string) $payload['status'];
            if ($status !== '' && !in_array($status, GalleryItem::STATUSES, true)) {
                return Lang::get('invalid_status', [], 'gallery');
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
