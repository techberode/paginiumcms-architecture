<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\Locking\Services\LockManager;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class LockingFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.1.locking';
    }

    public function group(): string
    {
        return 'platform';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it1_locking';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(LockManager::class)) {
            return $this->missing('Lock manager service is not registered.');
        }

        if (!$this->support->routeFileContains('locking.php', '/acquire')) {
            return $this->missing('Lock acquire route is not registered.');
        }

        return $this->implemented('Content locking API and manager are wired.', '2.0.0');
    }
}
