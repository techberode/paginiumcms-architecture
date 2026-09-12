<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Upload;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\MediaFormats;

/**
 * Single entry point for unified upload security (It.78).
 */
final class UploadPolicyEngine
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private UploadPolicyProfile $profiles,
        private UploadFilenameGuard $filenameGuard,
        private UploadMagicByteInspector $magicBytes,
        private UploadArchiveValidator $archiveValidator,
        private UploadQuotaGuard $quotaGuard,
        private UploadAuditLogger $auditLogger,
    ) {
    }

    public function isUnifiedEnabled(): bool
    {
        $cfg = $this->settings->group('uploadSecurity');

        return $this->isTruthy($cfg['unifiedPolicyEnabled'] ?? true);
    }

    public function assertFilenameAllowed(string $surfaceId, string $filename): void
    {
        if (!$this->isUnifiedEnabled()) {
            return;
        }

        $profileId = UploadSurfaceRegistry::profileForSurface($surfaceId);
        $this->filenameGuard->assertAllowed($filename, $this->profiles->allowedExtensions($profileId));
    }

    /**
     * @throws UploadPolicyException
     */
    public function enforceBinary(
        string $surfaceId,
        string $filename,
        string $binary,
        string $declaredMime,
        ?string $userId = null
    ): string {
        if (!$this->isUnifiedEnabled()) {
            return strtolower(trim($declaredMime));
        }

        $profileId = UploadSurfaceRegistry::profileForSurface($surfaceId);
        $sizeBytes = strlen($binary);
        $declaredMime = strtolower(trim($declaredMime));

        try {
            $this->filenameGuard->assertAllowed($filename, $this->profiles->allowedExtensions($profileId));

            $maxBytes = $this->profiles->maxBytes($profileId);
            if ($sizeBytes > $maxBytes) {
                throw new UploadPolicyException('Súbor presahuje maximálnu povolenú veľkosť');
            }

            $this->quotaGuard->assertWithinQuota($userId, $sizeBytes);

            $allowedMimeTypes = $this->profiles->allowedMimeTypes($profileId);
            if ($allowedMimeTypes === []) {
                throw new UploadPolicyException('Upload profil nemá povolené MIME typy');
            }

            try {
                $validatedMime = MediaFormats::validate(
                    $filename,
                    $binary,
                    $declaredMime,
                    $allowedMimeTypes,
                    $this->profiles->requiresMagicBytes($profileId)
                );
            } catch (FlatFileException $exception) {
                throw new UploadPolicyException($exception->getMessage(), 0, $exception);
            }

            $this->quotaGuard->recordUsage($userId, $sizeBytes);
            $this->auditLogger->log($surfaceId, $profileId, true, $userId, $filename, $sizeBytes, $validatedMime);

            return $validatedMime;
        } catch (UploadPolicyException $exception) {
            $this->auditLogger->log(
                $surfaceId,
                $profileId,
                false,
                $userId,
                $filename,
                $sizeBytes,
                $declaredMime,
                $exception->getMessage()
            );

            throw $exception;
        }
    }

    /**
     * @throws UploadPolicyException
     */
    public function enforceArchive(
        string $surfaceId,
        string $filename,
        int $sizeBytes,
        string $tempZipPath,
        ?string $userId = null
    ): void {
        if (!$this->isUnifiedEnabled()) {
            return;
        }

        $profileId = UploadSurfaceRegistry::profileForSurface($surfaceId);

        try {
            $this->filenameGuard->assertAllowed($filename, $this->profiles->allowedExtensions($profileId));

            $maxBytes = $this->profiles->maxBytes($profileId);
            if ($sizeBytes > $maxBytes) {
                throw new UploadPolicyException('Archív presahuje maximálnu povolenú veľkosť');
            }

            $this->quotaGuard->assertWithinQuota($userId, $sizeBytes);

            $header = is_readable($tempZipPath) ? (string) file_get_contents($tempZipPath, false, null, 0, 4) : '';
            if (!$this->magicBytes->looksLikeZip($header)) {
                throw new UploadPolicyException('Archív musí byť platný ZIP súbor');
            }

            $this->archiveValidator->assertSafeArchive($tempZipPath, $sizeBytes);

            $this->quotaGuard->recordUsage($userId, $sizeBytes);
            $this->auditLogger->log(
                $surfaceId,
                $profileId,
                true,
                $userId,
                $filename,
                $sizeBytes,
                'application/zip'
            );
        } catch (UploadPolicyException $exception) {
            $this->auditLogger->log(
                $surfaceId,
                $profileId,
                false,
                $userId,
                $filename,
                $sizeBytes,
                'application/zip',
                $exception->getMessage()
            );

            throw $exception;
        }
    }

    public function logOutboundImport(
        string $surfaceId,
        ?string $userId,
        string $url,
        int $sizeBytes,
        bool $allowed,
        ?string $reason = null
    ): void {
        if (!$this->isUnifiedEnabled()) {
            return;
        }

        $profileId = UploadSurfaceRegistry::profileForSurface($surfaceId);
        $this->auditLogger->log(
            $surfaceId,
            $profileId,
            $allowed,
            $userId,
            $url,
            $sizeBytes,
            'remote/fetch',
            $reason
        );
    }

    /**
     * @param list<string> $domainMimeTypes
     *
     * @return list<string>
     */
    public function resolveAllowedMimeTypes(string $surfaceId, array $domainMimeTypes): array
    {
        if (!$this->isUnifiedEnabled()) {
            return $domainMimeTypes;
        }

        $profileId = UploadSurfaceRegistry::profileForSurface($surfaceId);

        return $this->profiles->allowedMimeTypes($profileId);
    }

    public function resolveMaxUploadBytes(string $surfaceId): int
    {
        if (!$this->isUnifiedEnabled()) {
            return PHP_INT_MAX;
        }

        $profileId = UploadSurfaceRegistry::profileForSurface($surfaceId);

        return $this->profiles->maxBytes($profileId);
    }

    public function shouldScanMagicBytes(): bool
    {
        $cfg = $this->settings->group('uploadSecurity');

        return $this->isTruthy($cfg['scanMagicBytes'] ?? true);
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
