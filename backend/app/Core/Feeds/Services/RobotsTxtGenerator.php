<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Feeds\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Generates robots.txt with optional Sitemap directive (Iteration 10 polish).
 */
final class RobotsTxtGenerator
{
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
    }

    public function generate(): string
    {
        $feeds = $this->settings->group('feeds');
        $seo = $this->settings->group('seo');
        $general = $this->settings->group('general');
        $siteUrl = rtrim((string) ($general['siteUrl'] ?? ''), '/');
        if ($siteUrl === '') {
            $siteUrl = 'http://localhost:3025';
        }

        $allowIndexing = ($seo['allowSearchIndexing'] ?? true) !== false;

        $lines = [
            'User-agent: *',
            $allowIndexing ? 'Allow: /' : 'Disallow: /',
            '',
        ];

        if ($allowIndexing && ($feeds['enabled'] ?? true) !== false) {
            $lines[] = 'Sitemap: ' . $siteUrl . '/sitemap.xml';
        }

        return implode("\n", $lines) . "\n";
    }
}
