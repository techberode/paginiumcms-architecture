<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Admin\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Admin dashboard "getting started" probes — reads persisted settings (not public cache).
 */
final class GettingStartedStatusService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * @return array{
     *     siteName: bool,
     *     mail: bool
     * }
     */
    public function probes(): array
    {
        $general = $this->settings->group('general');
        $siteName = trim((string) ($general['siteName'] ?? ''));
        $siteDescription = trim((string) ($general['siteDescription'] ?? ''));

        $siteNameOk = !$this->isDefaultSiteName($siteName)
            || mb_strlen($siteDescription) >= 3;

        $branding = $this->settings->group('branding');
        if (!$siteNameOk) {
            $logo = trim((string) ($branding['logoUrl'] ?? ''));
            $favicon = trim((string) ($branding['faviconUrl'] ?? ''));
            $siteNameOk = $logo !== '' || $favicon !== '';
        }

        $smtp = $this->settings->group('smtp');
        $mailOk = trim((string) ($smtp['host'] ?? '')) !== ''
            && trim((string) ($smtp['fromEmail'] ?? '')) !== '';

        return [
            'siteName' => $siteNameOk,
            'mail' => $mailOk,
        ];
    }

    private function isDefaultSiteName(string $name): bool
    {
        $trimmed = trim($name);
        if ($trimmed === '' || mb_strlen($trimmed) < 2) {
            return true;
        }

        $normalized = mb_strtolower($trimmed);
        $normalized = preg_replace('/\s+/u', '', $normalized) ?? $normalized;

        return $normalized === 'paginiumcms';
    }
}
