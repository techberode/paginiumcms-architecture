<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Security\Upload\UploadPolicyEngine;
use PaginiumCMS\Core\Security\Upload\UploadPolicyException;
use PaginiumCMS\Core\Security\Upload\UploadSurfaceRegistry;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\MediaDocumentPolicy;
use PaginiumCMS\Modules\Media\MediaFormats;

/**
 * Upload hardening driven by settings.uploadSecurity (It.19b) and UploadPolicyEngine (It.78).
 */
final class UploadSecurityValidator
{
    /**
     * @var list<string>
     */
    private const EXECUTABLE_EXTENSIONS = [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8',
        'exe', 'sh', 'bat', 'cmd', 'com', 'js', 'mjs', 'cjs', 'htaccess',
    ];

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private UploadPolicyEngine $policyEngine,
    ) {
    }

    /**
     * @throws FlatFileException
     */
    public function assertFilenameAllowed(string $filename): void
    {
        $this->assertMediaUploadFilenameAllowed($filename, MediaFormats::guessMimeFromExtension($filename) ?? 'application/octet-stream');
    }

    /**
     * Media Library upload — route filename checks to the correct It.78 surface (image vs document vs video).
     *
     * @throws FlatFileException
     */
    public function assertMediaUploadFilenameAllowed(string $filename, string $declaredMime): void
    {
        $declaredMime = MediaFormats::coalesceDeclaredMime($filename, $declaredMime);

        if ($this->policyEngine->isUnifiedEnabled()) {
            try {
                $surface = $this->resolveMediaUploadSurface($filename, $declaredMime);
                $this->policyEngine->assertFilenameAllowed($surface, $filename);
            } catch (UploadPolicyException $exception) {
                throw new FlatFileException($exception->getMessage(), 0, $exception);
            }

            return;
        }

        $this->assertFilenameAllowedLegacy($filename);
    }

    private function resolveMediaUploadSurface(string $originalName, string $declaredMime): string
    {
        if (MediaFormats::isVideoMime($declaredMime)) {
            return UploadSurfaceRegistry::SURFACE_MEDIA_VIDEO_UPLOAD;
        }

        if (MediaFormats::isDocumentMime($declaredMime)) {
            return UploadSurfaceRegistry::SURFACE_MEDIA_DOCUMENT_UPLOAD;
        }

        $inferred = MediaFormats::guessMimeFromExtension($originalName);
        if ($inferred !== null) {
            if (MediaFormats::isVideoMime($inferred)) {
                return UploadSurfaceRegistry::SURFACE_MEDIA_VIDEO_UPLOAD;
            }

            if (MediaFormats::isDocumentMime($inferred)) {
                return UploadSurfaceRegistry::SURFACE_MEDIA_DOCUMENT_UPLOAD;
            }
        }

        return UploadSurfaceRegistry::SURFACE_MEDIA_UPLOAD;
    }

    public function shouldScanMagicBytes(): bool
    {
        return $this->policyEngine->shouldScanMagicBytes();
    }

    /**
     * @param list<string> $mediaMimeTypes
     *
     * @return list<string>
     */
    public function resolveAllowedMimeTypes(array $mediaMimeTypes): array
    {
        if ($this->policyEngine->isUnifiedEnabled()) {
            return $this->policyEngine->resolveAllowedMimeTypes(
                UploadSurfaceRegistry::SURFACE_MEDIA_UPLOAD,
                $mediaMimeTypes
            );
        }

        if ($mediaMimeTypes === []) {
            return $this->mediaPolicyMimeTypes();
        }

        return $this->resolveAllowedMimeTypesLegacy($mediaMimeTypes);
    }

    public function resolveMaxUploadBytes(int $mediaMaxBytes): int
    {
        if ($this->policyEngine->isUnifiedEnabled()) {
            return $this->policyEngine->resolveMaxUploadBytes(UploadSurfaceRegistry::SURFACE_MEDIA_UPLOAD);
        }

        return $this->resolveMaxUploadBytesLegacy($mediaMaxBytes);
    }

    /**
     * @throws FlatFileException
     */
    private function assertFilenameAllowedLegacy(string $filename): void
    {
        $cfg = $this->settings->group('uploadSecurity');

        if ($this->isTruthy($cfg['blockDoubleExtensions'] ?? true)) {
            $this->assertNoDoubleExtension($filename);
        }

        if ($this->isTruthy($cfg['blockExecutables'] ?? true)) {
            $this->assertNotExecutableExtension($filename);
        }

        $this->assertExtensionWhitelisted($filename, $cfg);
    }

    /**
     * @param list<string> $mediaMimeTypes
     *
     * @return list<string>
     */
    private function resolveAllowedMimeTypesLegacy(array $mediaMimeTypes): array
    {
        $securityTypes = $this->parseCsv((string) ($this->settings->group('uploadSecurity')['allowedMimeTypes'] ?? ''));
        if ($securityTypes === []) {
            return $mediaMimeTypes;
        }

        $securityTypes = array_values(array_filter(
            $securityTypes,
            static fn (string $type): bool => MediaFormats::isKnownMime($type)
        ));

        if ($mediaMimeTypes === []) {
            return $securityTypes;
        }

        return array_values(array_intersect($mediaMimeTypes, $securityTypes));
    }

    private function resolveMaxUploadBytesLegacy(int $mediaMaxBytes): int
    {
        $securityKb = (int) ($this->settings->group('uploadSecurity')['maxUploadSizeKb'] ?? 0);
        if ($securityKb <= 0) {
            return $mediaMaxBytes;
        }

        $securityBytes = max(64, $securityKb) * 1024;

        return min($mediaMaxBytes, $securityBytes);
    }

    /**
     * @throws FlatFileException
     */
    private function assertNoDoubleExtension(string $filename): void
    {
        $base = basename($filename);
        $parts = explode('.', $base);
        if (count($parts) < 3) {
            return;
        }

        for ($index = 0; $index < count($parts) - 1; $index++) {
            $segment = strtolower($parts[$index]);
            if (in_array($segment, self::EXECUTABLE_EXTENSIONS, true)) {
                throw new FlatFileException('Súbor obsahuje zakázanú dvojitú príponu');
            }
        }
    }

    /**
     * @throws FlatFileException
     */
    private function assertNotExecutableExtension(string $filename): void
    {
        $extension = strtolower(pathinfo(basename($filename), PATHINFO_EXTENSION));
        if ($extension === '') {
            return;
        }

        if (in_array($extension, self::EXECUTABLE_EXTENSIONS, true)) {
            throw new FlatFileException('Spustiteľné typy súborov nie sú povolené');
        }
    }

    /**
     * MIME policy used by both the allow-list merge and resolveAllowedMimeTypes().
     * Kept separate so assertExtensionWhitelisted() never recurses through resolveAllowedMimeTypes().
     *
     * @return list<string>
     */
    private function mediaPolicyMimeTypes(): array
    {
        return $this->resolveAllowedMimeTypesLegacy(MediaFormats::defaultMimeTypes());
    }

    /**
     * @param array<string, mixed> $cfg
     *
     * @throws FlatFileException
     */
    private function assertExtensionWhitelisted(string $filename, array $cfg): void
    {
        $allowed = $this->parseCsv((string) ($cfg['allowedExtensions'] ?? ''));

        $allowedFromMimeTypes = MediaFormats::toApiPayload($this->mediaPolicyMimeTypes())['extensions'];
        $allowed = array_values(array_unique(array_merge($allowed, $allowedFromMimeTypes)));

        if (MediaDocumentPolicy::isEnabled($this->settings)) {
            $documentExtensions = MediaFormats::toApiPayload(
                MediaDocumentPolicy::allowedMimeTypes($this->settings)
            )['extensions'];
            $allowed = array_values(array_unique(array_merge($allowed, $documentExtensions)));
        }

        if ($allowed === []) {
            return;
        }

        $extension = strtolower(pathinfo(basename($filename), PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, $allowed, true)) {
            throw new FlatFileException('Prípona súboru nie je v povolenom zozname');
        }
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
