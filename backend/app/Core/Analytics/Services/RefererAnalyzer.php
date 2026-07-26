<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

/**
 * Normalizes HTTP referer values for analytics reports (Iteration 33).
 */
final class RefererAnalyzer
{
    /** @var list<string> */
    private const SEARCH_HOST_FRAGMENTS = [
        'google.',
        'bing.',
        'duckduckgo.',
        'yahoo.',
        'yandex.',
        'ecosia.',
        'seznam.',
        'search.',
    ];

    /** @var list<string> */
    private const SOCIAL_HOST_FRAGMENTS = [
        'facebook.',
        'fb.',
        'twitter.',
        'x.com',
        't.co',
        'linkedin.',
        'instagram.',
        'tiktok.',
        'reddit.',
        'pinterest.',
        'youtube.',
    ];

    /**
     * @return array{referer: string, source: string, domain: string, type: string}
     */
    public function analyze(string $referer): array
    {
        $referer = trim($referer);
        if ($referer === '' || strtolower($referer) === 'direct') {
            return [
                'referer' => 'direct',
                'source' => 'Direct',
                'domain' => '',
                'type' => 'direct',
            ];
        }

        $domain = $this->extractDomain($referer);
        if ($domain === '') {
            return [
                'referer' => $referer,
                'source' => $this->truncateLabel($referer),
                'domain' => '',
                'type' => 'referral',
            ];
        }

        $type = $this->resolveType($domain);
        $source = $this->resolveSourceLabel($domain, $type);

        return [
            'referer' => $referer,
            'source' => $source,
            'domain' => $domain,
            'type' => $type,
        ];
    }

    public function groupingKey(string $referer): string
    {
        $analysis = $this->analyze($referer);

        if ($analysis['type'] === 'direct') {
            return 'direct';
        }

        return $analysis['domain'] !== '' ? $analysis['domain'] : $analysis['referer'];
    }

    private function extractDomain(string $referer): string
    {
        if (!str_contains($referer, '://')) {
            $referer = 'https://' . ltrim($referer, '/');
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return '';
        }

        return strtolower($host);
    }

    private function resolveType(string $domain): string
    {
        foreach (self::SEARCH_HOST_FRAGMENTS as $fragment) {
            if (str_contains($domain, $fragment)) {
                return 'search';
            }
        }

        foreach (self::SOCIAL_HOST_FRAGMENTS as $fragment) {
            if (str_contains($domain, $fragment)) {
                return 'social';
            }
        }

        return 'referral';
    }

    private function resolveSourceLabel(string $domain, string $type): string
    {
        return match ($type) {
            'search' => $this->searchLabel($domain),
            'social' => $this->socialLabel($domain),
            default => $this->prettifyDomain($domain),
        };
    }

    private function searchLabel(string $domain): string
    {
        if (str_contains($domain, 'google.')) {
            return 'Google';
        }
        if (str_contains($domain, 'bing.')) {
            return 'Bing';
        }
        if (str_contains($domain, 'duckduckgo.')) {
            return 'DuckDuckGo';
        }
        if (str_contains($domain, 'seznam.')) {
            return 'Seznam';
        }

        return $this->prettifyDomain($domain);
    }

    private function socialLabel(string $domain): string
    {
        if (str_contains($domain, 'facebook.') || str_contains($domain, 'fb.')) {
            return 'Facebook';
        }
        if (str_contains($domain, 'twitter.') || str_contains($domain, 'x.com') || str_contains($domain, 't.co')) {
            return 'X (Twitter)';
        }
        if (str_contains($domain, 'linkedin.')) {
            return 'LinkedIn';
        }
        if (str_contains($domain, 'instagram.')) {
            return 'Instagram';
        }
        if (str_contains($domain, 'youtube.')) {
            return 'YouTube';
        }

        return $this->prettifyDomain($domain);
    }

    private function prettifyDomain(string $domain): string
    {
        $label = preg_replace('/^www\./', '', $domain) ?? $domain;
        $parts = explode('.', $label);
        if (count($parts) >= 2) {
            $label = $parts[count($parts) - 2];
        }

        return ucfirst($label);
    }

    private function truncateLabel(string $value): string
    {
        if (strlen($value) <= 48) {
            return $value;
        }

        return substr($value, 0, 45) . '...';
    }
}
