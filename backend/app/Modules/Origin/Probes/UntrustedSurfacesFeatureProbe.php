<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class UntrustedSurfacesFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.67.untrusted_surfaces';
    }

    public function group(): string
    {
        return 'security';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it67_untrusted';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(CodePolicyEngine::class)) {
            return $this->missing('Code policy engine is not available.');
        }

        if (!$this->support->anyRouteFileContains('SecurityMiddleware')) {
            return $this->partial('Code policy exists; security middleware wiring should be verified.');
        }

        return $this->implemented('Untrusted-surface hardening services are present.', '2.1.0-beta.27');
    }
}
