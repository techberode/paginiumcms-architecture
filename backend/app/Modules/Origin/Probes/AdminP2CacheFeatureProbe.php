<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\Cache\AdminOverviewCacheService;
use PaginiumCMS\Core\Cache\AdminOverviewCacheWarmer;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class AdminP2CacheFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.100.admin_p2_cache';
    }

    public function group(): string
    {
        return 'platform';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it100_admin_p2_cache';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(AdminOverviewCacheService::class)) {
            return $this->missing('AdminOverviewCacheService is not registered.');
        }

        if (!$this->support->classAvailable(AdminOverviewCacheWarmer::class)) {
            return $this->partial('P2 cache service exists; warmer should be verified.');
        }

        if (!$this->support->appSourceContains('Core/Scheduler/Handlers/AdminOverviewCacheWarmHandler.php', 'admin_cache.warm')) {
            return $this->partial('Warmer exists; scheduler job handler should be verified.');
        }

        if (!$this->support->appSourceContains('Core/Performance/AdminActiveSessionStore.php', 'active_sessions')) {
            return $this->partial('P2 cache shipped; session load-hint (100f) should be verified.');
        }

        return $this->implemented(
            'Admin P2 SWR cache, warm job, cron-only HTTP option, load-hint sessions/host, P3 async log export (It.100).',
            '2.1.0-beta.98'
        );
    }
}
