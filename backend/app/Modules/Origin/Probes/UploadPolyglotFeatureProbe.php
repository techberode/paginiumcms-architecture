<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\Security\Upload\PolyglotUploadGuard;
use PaginiumCMS\Core\Security\Upload\UploadPolicyEngine;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class UploadPolyglotFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.99.upload_polyglot';
    }

    public function group(): string
    {
        return 'security';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it99_polyglot';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(PolyglotUploadGuard::class)) {
            return $this->missing('PolyglotUploadGuard is not registered.');
        }

        if (!$this->support->classAvailable(UploadPolicyEngine::class)) {
            return $this->missing('UploadPolicyEngine is not registered.');
        }

        if (!$this->support->appSourceContains('Core/Security/Upload/UploadPolicyEngine.php', 'polyglotGuard')) {
            return $this->partial('Polyglot guard class exists; unified upload wiring should be verified.');
        }

        return $this->implemented(
            'Upload-time marker scan, SVG/PDF probes, and optional raster re-encode (It.99).',
            '2.1.0-beta.98'
        );
    }
}
