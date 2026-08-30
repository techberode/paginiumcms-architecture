<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class ThemePackagesFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.83.theme_packages';
    }

    public function group(): string
    {
        return 'layout';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it83_theme_packages';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(\PaginiumCMS\Http\Themes\Services\ThemeCatalogSeeder::class)) {
            return $this->missing('ThemeCatalogSeeder is not available.');
        }

        if (!$this->support->frontendSourceContains('theme/themeShellRegistry.ts', 'terminal-breach')) {
            return $this->partial('PublicShell registry missing bundled terminal-breach theme.');
        }

        return $this->implemented('Bundled theme packages and PublicShell registry include terminal-breach.', '2.1.0-beta.59');
    }
}
