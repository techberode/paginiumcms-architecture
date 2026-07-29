<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Firewall;

/**
 * Decides whether mutating request bodies should be scanned by the WAF.
 *
 * Content-heavy editor routes are exempt to avoid false positives on markdown/SQL snippets.
 */
final class FirewallBodyScanPolicy
{
    /** @var list<string> */
    private const EXEMPT_PREFIXES = [
        '/api/pages',
        '/api/articles',
        '/api/drafts',
        '/api/admin/code-editor',
        '/api/webhooks/',
    ];

    public function shouldScan(string $method, string $uriPath, bool $enabledInSettings): bool
    {
        if (!$enabledInSettings) {
            return false;
        }

        if (!in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        foreach (self::EXEMPT_PREFIXES as $prefix) {
            if (str_starts_with($uriPath, $prefix)) {
                return false;
            }
        }

        return true;
    }
}
