<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Security\CspScriptSrcContributorInterface;

final class ThemeCspScriptSrcContributor implements CspScriptSrcContributorInterface
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private ThemeRuntimeService $runtime,
        private ThemeScriptIntegrityService $integrity,
    ) {
    }

    public function extraScriptSrcTokens(): array
    {
        $appearance = $this->settings->group('appearance');
        if (!(bool) ($appearance['themeScriptsEnabled'] ?? false)) {
            return [];
        }

        $themeId = $this->runtime->resolveActiveThemeId();
        if ($themeId === ThemeRuntimeService::CORE_THEME_ID) {
            return [];
        }

        return $this->integrity->cspHashTokens($themeId);
    }
}
