<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\FlatFile\Services\ContentScheduledPublishService;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class ScheduledPublishFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.59.scheduled_publish';
    }

    public function group(): string
    {
        return 'content';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it59_scheduled';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(ContentScheduledPublishService::class)) {
            return $this->missing('Scheduled publish service is not registered.');
        }

        if (!$this->support->anyRouteFileContains('scheduledAt')) {
            return $this->partial('Scheduled publish service exists; scheduledAt exposure should be verified.');
        }

        return $this->implemented('Scheduled publishing service and content fields are wired.', '2.0.53');
    }
}
