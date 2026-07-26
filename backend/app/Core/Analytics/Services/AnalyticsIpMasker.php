<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

/**
 * GDPR-aware IP masking for analytics admin UI (Iteration 33).
 */
final class AnalyticsIpMasker
{
    public static function mask(?string $ip): string
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return 'unknown';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.xxx.xxx';
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $segments = explode(':', $ip);
            $prefix = implode(':', array_slice($segments, 0, 2));

            return $prefix . ':xxxx:xxxx:xxxx';
        }

        return 'unknown';
    }
}
