<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Audit trail for trusted HTML / embed content saves (It.91c).
 */
final class TrustedContentAuditLogger
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private TrustedContentDetector $detector,
        private ?SecurityAuditStore $auditStore = null,
    ) {
    }

    public function logContentSave(
        ?User $user,
        string $contentType,
        string $slug,
        string $contentFormat,
        string $content
    ): void {
        if (!$this->shouldAudit() || $contentFormat !== 'markdown') {
            return;
        }

        if (!$this->detector->hasTrustedBlocks($content)) {
            return;
        }

        $counts = $this->detector->countBlocks($content);
        $metadata = [
            'content_type' => LogSanitizer::value($contentType, 32),
            'slug' => LogSanitizer::value($slug, 180),
            'format' => LogSanitizer::value($contentFormat, 32),
            'html_safe_blocks' => (string) $counts['html_safe'],
            'embed_blocks' => (string) $counts['embed'],
            'body_hash' => hash('sha256', $content),
        ];

        $this->auditStore?->append(
            'trusted_content_save',
            LogSeverity::INFO,
            'Trusted content saved',
            $user?->getId(),
            $user?->getEmail(),
            null,
            $metadata
        );
    }

    private function shouldAudit(): bool
    {
        $cfg = $this->settings->group('editor');

        return $this->isTruthy($cfg['auditTrustedContent'] ?? true);
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
