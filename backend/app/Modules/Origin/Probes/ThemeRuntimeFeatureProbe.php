<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class ThemeRuntimeFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.83.theme_runtime';
    }

    public function group(): string
    {
        return 'layout';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it83_theme_runtime';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(\PaginiumCMS\Http\Themes\Services\ThemeRuntimeService::class)) {
            return $this->missing('ThemeRuntimeService is not registered.');
        }

        if (!$this->support->routeFileContains('themes.php', 'activate')) {
            return $this->missing('Theme activate/deactivate routes are missing.');
        }

        if (!$this->support->appSourceContains('Core/Settings/SettingsSchema.php', 'activeThemeId')) {
            return $this->partial('appearance.activeThemeId setting is not declared.');
        }

        return $this->implemented('Theme runtime activate/deactivate and activeThemeId settings are wired.', '2.1.0-beta.59');
    }
}
