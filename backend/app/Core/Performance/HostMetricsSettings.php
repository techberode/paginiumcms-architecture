<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

final class HostMetricsSettings
{
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
    }

    public function enabled(): bool
    {
        $engine = $this->settings->group('engine');

        return (bool) ($engine['hostMetricsEnabled'] ?? false);
    }

    public function maxAgeSeconds(): int
    {
        $engine = $this->settings->group('engine');
        $value = (int) ($engine['hostMetricsMaxAgeSeconds'] ?? 600);

        return max(60, min(86400, $value));
    }

    public function ingestToken(): string
    {
        $engine = $this->settings->group('engine');

        return trim((string) ($engine['hostMetricsIngestToken'] ?? ''));
    }
}
