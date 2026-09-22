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

        if (!$this->support->classAvailable(\PaginiumCMS\Http\Middleware\SecurityMiddleware::class)) {
            return $this->missing('Security middleware is not registered.');
        }

        if (!$this->support->appSourceContains('Http/Middleware/SecurityMiddleware.php', 'frame-ancestors')) {
            return $this->partial('Security middleware exists; CSP hardening should be verified.');
        }

        return $this->implemented('Untrusted-surface hardening and CSP middleware are wired.', '2.1.0-beta.27');
    }
}
