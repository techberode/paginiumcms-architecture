<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Engine settings for admin P2 derived cache (Iteration 100d).
 */
final class AdminOverviewCacheSettings
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    /**
     * When true, HTTP never runs P2 flat-file loaders on cache miss — only cron/CLI warm builds envelopes.
     */
    public function cronOnlyHttp(): bool
    {
        $engine = $this->settings->group('engine');

        return (bool) ($engine['adminP2CacheCronOnly'] ?? false);
    }
}
