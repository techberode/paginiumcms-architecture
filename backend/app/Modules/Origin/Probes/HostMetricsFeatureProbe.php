<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\Performance\HostMetricsCollector;
use PaginiumCMS\Core\Performance\HostMetricsService;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class HostMetricsFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.82.host_metrics';
    }

    public function group(): string
    {
        return 'platform';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it82_host_metrics';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(HostMetricsCollector::class)
            || !$this->support->classAvailable(HostMetricsService::class)) {
            return $this->missing('Host metrics services are not registered.');
        }

        if (!$this->support->routeFileContains('metrics.php', 'ingestHost')) {
            return $this->partial('Collector exists; host ingest route should be verified.');
        }

        return $this->implemented(
            'Host snapshot store, CLI metrics:host-collect, and loopback ingest are wired.',
            '2.1.0-beta.98'
        );
    }
}
