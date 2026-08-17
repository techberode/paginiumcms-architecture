<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class RedirectsFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.80.redirects';
    }

    public function group(): string
    {
        return 'platform';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it80_redirects';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->routeFileContains('redirects.php', 'RedirectController')) {
            return $this->missing('Redirect manager admin routes are not registered.');
        }

        return $this->implemented('Redirect manager API is registered.', '2.1.0-beta.32');
    }
}
