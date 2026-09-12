<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Upload;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Optional per-user daily upload quota (flat-file counter, It.78).
 */
final class UploadQuotaGuard
{
    private const QUOTA_DIR = 'data/security/upload_quota';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
    ) {
    }

    public function assertWithinQuota(?string $userId, int $bytes): void
    {
        $limit = $this->dailyLimitBytes();
        if ($limit <= 0 || $userId === null || $userId === '' || $bytes <= 0) {
            return;
        }

        $used = $this->readUsage($userId);
        if ($used + $bytes > $limit) {
            throw new UploadPolicyException('Denný upload limit bol prekročený');
        }
    }

    public function recordUsage(?string $userId, int $bytes): void
    {
        $limit = $this->dailyLimitBytes();
        if ($limit <= 0 || $userId === null || $userId === '' || $bytes <= 0) {
            return;
        }

        $path = $this->quotaPath($userId);
        $used = $this->readUsage($userId) + $bytes;
        $payload = JsonHelper::encode([
            'userId' => $userId,
            'date' => gmdate('Y-m-d'),
            'bytes' => $used,
        ], JSON_UNESCAPED_UNICODE);

        $this->writer->write($path, $payload, true);
    }

    private function dailyLimitBytes(): int
    {
        $cfg = $this->settings->group('uploadSecurity');

        return max(0, (int) ($cfg['dailyQuotaBytesPerUser'] ?? 0));
    }

    private function readUsage(string $userId): int
    {
        $path = $this->quotaPath($userId);
        if (!$this->reader->exists($path)) {
            return 0;
        }

        try {
            $raw = $this->reader->read($path);
            $data = JsonHelper::decode($raw);

            if (($data['date'] ?? '') !== gmdate('Y-m-d')) {
                return 0;
            }

            return max(0, (int) ($data['bytes'] ?? 0));
        } catch (\Throwable) {
            return 0;
        }
    }

    private function quotaPath(string $userId): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $userId) ?? 'unknown';

        return self::QUOTA_DIR . '/' . $safeId . '.json';
    }
}
