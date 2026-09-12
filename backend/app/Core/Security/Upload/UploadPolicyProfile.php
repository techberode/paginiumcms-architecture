<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Upload;

use PaginiumCMS\Core\Content\AvatarImageProcessor;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\MediaFormats;

/**
 * Resolves effective limits and allow-lists per profile (It.78).
 */
final class UploadPolicyProfile
{
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * @return list<string>
     */
    public function allowedMimeTypes(string $profileId): array
    {
        return match ($profileId) {
            UploadPolicyProfileId::MEDIA => $this->intersectMimeTypes($this->resolveMediaMimeTypes()),
            UploadPolicyProfileId::AVATAR => AvatarImageProcessor::ALLOWED_MIMES,
            UploadPolicyProfileId::MEDIA_VIDEO => $this->intersectMimeTypes($this->resolveMediaMimeTypes()),
            UploadPolicyProfileId::BACKUP_ARCHIVE,
            UploadPolicyProfileId::EXTENSION_ARCHIVE => ['application/zip', 'application/x-zip-compressed'],
            UploadPolicyProfileId::STOCK_IMPORT => $this->intersectMimeTypes($this->resolveMediaMimeTypes()),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public function allowedExtensions(string $profileId): array
    {
        return match ($profileId) {
            UploadPolicyProfileId::MEDIA => $this->extensionsForMimes($this->allowedMimeTypes($profileId)),
            UploadPolicyProfileId::AVATAR => ['jpg', 'jpeg', 'png', 'webp'],
            UploadPolicyProfileId::MEDIA_VIDEO => $this->extensionsForMimes($this->allowedMimeTypes($profileId)),
            UploadPolicyProfileId::BACKUP_ARCHIVE,
            UploadPolicyProfileId::EXTENSION_ARCHIVE => ['zip'],
            UploadPolicyProfileId::STOCK_IMPORT => $this->extensionsForMimes($this->allowedMimeTypes($profileId)),
            default => [],
        };
    }

    public function maxBytes(string $profileId): int
    {
        $candidates = [];

        $globalKb = (int) ($this->settings->group('uploadSecurity')['maxUploadSizeKb'] ?? 0);
        if ($globalKb > 0) {
            $candidates[] = max(64, $globalKb) * 1024;
        }

        $profileMax = match ($profileId) {
            UploadPolicyProfileId::MEDIA => $this->resolveMediaMaxUploadBytes(),
            UploadPolicyProfileId::AVATAR => AvatarImageProcessor::MAX_UPLOAD_BYTES,
            UploadPolicyProfileId::MEDIA_VIDEO => $this->resolveMediaMaxUploadBytes(),
            UploadPolicyProfileId::BACKUP_ARCHIVE => max(1024, (int) ($this->settings->group('uploadSecurity')['backupImportMaxSizeKb'] ?? 102400)) * 1024,
            UploadPolicyProfileId::EXTENSION_ARCHIVE => 52_428_800,
            UploadPolicyProfileId::STOCK_IMPORT => $this->resolveMediaMaxUploadBytes(),
            default => 0,
        };

        if ($profileMax > 0) {
            $candidates[] = $profileMax;
        }

        if ($candidates === []) {
            return PHP_INT_MAX;
        }

        return min($candidates);
    }

    public function requiresMagicBytes(string $profileId): bool
    {
        if ($this->isArchiveProfile($profileId)) {
            return true;
        }

        $cfg = $this->settings->group('uploadSecurity');

        return $this->isTruthy($cfg['scanMagicBytes'] ?? true);
    }

    public function isArchiveProfile(string $profileId): bool
    {
        return in_array($profileId, [
            UploadPolicyProfileId::BACKUP_ARCHIVE,
            UploadPolicyProfileId::EXTENSION_ARCHIVE,
        ], true);
    }

    /**
     * @param list<string> $domainMimeTypes
     *
     * @return list<string>
     */
    private function intersectMimeTypes(array $domainMimeTypes): array
    {
        $securityTypes = $this->parseCsv((string) ($this->settings->group('uploadSecurity')['allowedMimeTypes'] ?? ''));
        if ($securityTypes === []) {
            return $domainMimeTypes;
        }

        $securityTypes = array_values(array_filter(
            $securityTypes,
            static fn (string $type): bool => MediaFormats::isKnownMime($type)
        ));

        if ($domainMimeTypes === []) {
            return $securityTypes;
        }

        return array_values(array_intersect($domainMimeTypes, $securityTypes));
    }

    /**
     * @return list<string>
     */
    private function resolveMediaMimeTypes(): array
    {
        $raw = (string) ($this->settings->group('media')['allowedMimeTypes'] ?? '');
        $parsed = $this->parseCsv($raw);

        if ($parsed === []) {
            return MediaFormats::defaultMimeTypes();
        }

        return array_values(array_filter(
            $parsed,
            static fn (string $mime): bool => MediaFormats::isKnownMime($mime)
        ));
    }

    private function resolveMediaMaxUploadBytes(): int
    {
        $mediaKb = (int) ($this->settings->group('media')['maxUploadSizeKb'] ?? 5120);

        return max(64, $mediaKb) * 1024;
    }

    /**
     * @param list<string> $mimeTypes
     *
     * @return list<string>
     */
    private function extensionsForMimes(array $mimeTypes): array
    {
        $extensions = [];
        foreach ($mimeTypes as $mimeType) {
            $payload = MediaFormats::toApiPayload([$mimeType]);
            foreach ($payload['extensions'] as $extension) {
                $extensions[] = $extension;
            }
        }

        return array_values(array_unique($extensions));
    }

    /**
     * @return list<string>
     */
    private function parseCsv(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (string $part): string => strtolower(trim($part)), explode(',', $raw)),
            static fn (string $part): bool => $part !== ''
        ));
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        $normalized = strtolower(trim((string) $value));

        return !in_array($normalized, ['', '0', 'false', 'off', 'no'], true);
    }
}
