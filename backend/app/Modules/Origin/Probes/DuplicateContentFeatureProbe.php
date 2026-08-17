<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class DuplicateContentFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.81.duplicate';
    }

    public function group(): string
    {
        return 'content';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it81_duplicate';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->routeFileContains('content.php', '/duplicate')) {
            return $this->missing('Duplicate content routes are not registered.');
        }

        return $this->implemented('Page/article duplicate endpoints are registered.', '2.1.0-beta.47');
    }
}
