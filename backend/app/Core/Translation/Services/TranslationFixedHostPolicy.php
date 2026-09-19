<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

use PaginiumCMS\Core\Translation\Exception\TranslationException;

/**
 * Cloud translation endpoints are vendor-fixed (It.77). Admin cannot set an arbitrary URL.
 */
final class TranslationFixedHostPolicy
{
    /** @var array<string, list<string>> */
    private const HOSTS = [
        'deepl' => ['api-free.deepl.com', 'api.deepl.com'],
        'google' => ['translation.googleapis.com'],
    ];

    public function assertProviderUrl(string $provider, string $url): void
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $allowed = self::HOSTS[$provider] ?? [];
        if ($host === '' || !in_array($host, $allowed, true)) {
            throw new TranslationException('Translation provider URL is not allowed', 422, 'SSRF_BLOCKED');
        }
    }
}
