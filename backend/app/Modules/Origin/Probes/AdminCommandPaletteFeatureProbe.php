<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class AdminCommandPaletteFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.86.admin_search';
    }

    public function group(): string
    {
        return 'platform';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it86_admin_search';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->appSourceContains('Http/Controllers/Content/SearchController.php', 'AuthenticationInterface')) {
            return $this->missing('SearchController does not resolve session auth for admin scope.');
        }

        if (!$this->support->appSourceContains('Http/Config/services.php', 'get(AuthenticationInterface::class)')) {
            return $this->partial('SearchController DI wiring for AuthenticationInterface may be incomplete (ISS-159).');
        }

        if (!$this->support->frontendSourceContains('components/backend/AdminCommandPalette.tsx', 'buildLocalAdminRouteItems')) {
            return $this->partial('Admin command palette module catalog not wired on frontend.');
        }

        return $this->implemented('Admin command palette resolves session auth and exposes quick jumps.', 'unreleased');
    }
}
