<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\StaticSite;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Reads engine.renderMode (It.48). Missing key keeps Classic dynamic public site.
 */
final class StaticSiteSettings
{
    public const MODE_DYNAMIC = 'dynamic';
    public const MODE_HYBRID = 'hybrid';
    public const MODE_STATIC = 'static';

    /** @var list<string> */
    public const MODES = [self::MODE_DYNAMIC, self::MODE_HYBRID, self::MODE_STATIC];

    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    public function renderMode(): string
    {
        $engine = $this->settings->group('engine');
        $mode = (string) ($engine['renderMode'] ?? self::MODE_DYNAMIC);

        return in_array($mode, self::MODES, true) ? $mode : self::MODE_DYNAMIC;
    }

    public function autoRebuildOnWrite(): bool
    {
        return $this->renderMode() !== self::MODE_DYNAMIC;
    }

    public function servesPublicHtml(): bool
    {
        return $this->renderMode() !== self::MODE_DYNAMIC;
    }

    public function htmlLang(): string
    {
        $general = $this->settings->group('general');
        $lang = strtolower(trim((string) ($general['language'] ?? 'sk')));

        return preg_match('/^[a-z]{2}$/', $lang) === 1 ? $lang : 'sk';
    }
}
