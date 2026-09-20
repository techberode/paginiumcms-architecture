<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\StaticSite\StaticSiteGenerator;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class StaticSiteFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.48.static';
    }

    public function group(): string
    {
        return 'engine';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it48_static';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(StaticSiteGenerator::class)) {
            return $this->missing('Static site generator is missing.');
        }

        if (!$this->support->routeFileContains('static.php', 'StaticSiteController')) {
            return $this->partial('Generator exists; admin routes may be incomplete.');
        }

        return $this->implemented('Static compile/cache (58g) writes storage/app/static/. Public HTML serve is It.48b.', '2.1.0-beta.89');
    }
}
