<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * It.100b / 100f — thresholds for admin load-hint policy.
 */
final class AdminLoadHintSettings
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    /**
     * When active admin session count ≥ this value, load hint becomes busy (0 = off).
     */
    public function activeSessionsBusyThreshold(): int
    {
        $engine = $this->settings->group('engine');
        $value = (int) ($engine['adminLoadHintActiveSessionsMin'] ?? 0);

        return max(0, min(500, $value));
    }

    public function p3AdmissionWhenBusy(): bool
    {
        $engine = $this->settings->group('engine');

        return (bool) ($engine['adminP3AdmissionWhenBusy'] ?? true);
    }

    public function p3RetryAfterSeconds(): int
    {
        $engine = $this->settings->group('engine');
        $value = (int) ($engine['adminP3RetryAfterSeconds'] ?? 120);

        return max(30, min(3600, $value));
    }
}
