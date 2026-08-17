<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\Performance\PerformanceSampleStore;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class PerformanceGuardFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.71.performance_guard';
    }

    public function group(): string
    {
        return 'platform';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it71_performance';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(PerformanceSampleStore::class)) {
            return $this->missing('Performance sample store is not registered.');
        }

        if (!$this->support->routeFileContains('metrics.php', 'MetricsController')) {
            return $this->partial('Performance store exists; metrics API route should be verified.');
        }

        return $this->implemented('Performance Guard storage and metrics API are wired.', '2.1.0-beta.28');
    }
}
