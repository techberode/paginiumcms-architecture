<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class StaleContentFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.81.stale_content';
    }

    public function group(): string
    {
        return 'content';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it81_stale';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(ContentStalenessService::class)) {
            return $this->missing('Content staleness service is not registered.');
        }

        if (!$this->support->anyRouteFileContains("'stale'")) {
            return $this->partial('Staleness service exists; list filter wiring should be verified.');
        }

        return $this->implemented('Stale content service and list filter are wired.', '2.1.0-beta.47');
    }
}
