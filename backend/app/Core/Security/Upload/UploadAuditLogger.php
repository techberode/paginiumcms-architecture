<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Upload;

use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Sanitized upload audit events (It.78).
 */
final class UploadAuditLogger
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private ?SecurityAuditStore $auditStore = null,
    ) {
    }

    public function log(
        string $surfaceId,
        string $profileId,
        bool $allowed,
        ?string $userId,
        string $filename,
        int $sizeBytes,
        string $mimeType,
        ?string $reason = null
    ): void {
        if (!$this->shouldAudit()) {
            return;
        }

        $message = $allowed ? 'Upload allowed' : 'Upload rejected';
        $metadata = [
            'surface' => LogSanitizer::value($surfaceId, 64),
            'profile' => LogSanitizer::value($profileId, 64),
            'filename' => LogSanitizer::value($filename, 180),
            'size_bytes' => (string) $sizeBytes,
            'mime' => LogSanitizer::value($mimeType, 120),
            'outcome' => $allowed ? 'allowed' : 'rejected',
            'reason' => $reason !== null ? LogSanitizer::value($reason, 240) : '',
        ];

        $this->auditStore?->append(
            'upload_policy',
            $allowed ? LogSeverity::INFO : LogSeverity::WARNING,
            $message,
            $userId,
            null,
            null,
            $metadata
        );
    }

    private function shouldAudit(): bool
    {
        $cfg = $this->settings->group('uploadSecurity');

        return $this->isTruthy($cfg['auditUploads'] ?? true);
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
