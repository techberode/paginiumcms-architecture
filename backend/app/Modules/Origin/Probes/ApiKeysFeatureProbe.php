<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;
use PaginiumCMS\Modules\Security\Services\ApiKeyStore;

final class ApiKeysFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.74.api_keys';
    }

    public function group(): string
    {
        return 'security';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it74_api_keys';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(ApiKeyStore::class)) {
            return $this->missing('API key store is not registered.');
        }

        if (!$this->support->routeFileContains('apikeys.php', 'ApiKeyController')) {
            return $this->missing('API key admin routes are not registered.');
        }

        return $this->implemented('Headless API keys and admin routes are wired.', '2.1.0-beta.30');
    }
}
