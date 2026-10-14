<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security;

/**
 * Resolves the client IP behind optional trusted reverse proxies.
 */
final class ClientIpResolver
{
    /**
     * @param array<int|string, mixed> $trustedProxies
     * @return list<string>
     */
    public static function normalizeTrustedProxies(array $trustedProxies): array
    {
        $normalized = [];
        foreach ($trustedProxies as $proxy) {
            if (!is_string($proxy) && !is_int($proxy)) {
                continue;
            }
            $value = trim((string) $proxy);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed>|null $serverParams
     * @param list<string> $trustedProxies
     */
    public static function resolve(?array $serverParams = null, array $trustedProxies = []): string
    {
        $server = $serverParams ?? $_SERVER;
        $remoteAddr = (string) ($server['REMOTE_ADDR'] ?? 'unknown');

        if ($trustedProxies === [] || !in_array($remoteAddr, $trustedProxies, true)) {
            return $remoteAddr;
        }

        $forwardedFor = (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($forwardedFor === '') {
            return $remoteAddr;
        }

        $chain = [];
        foreach (explode(',', $forwardedFor) as $part) {
            $candidate = trim($part);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                $chain[] = $candidate;
            }
        }
        $chain[] = $remoteAddr;

        for ($index = count($chain) - 1; $index >= 0; $index--) {
            $candidate = $chain[$index];
            if (!in_array($candidate, $trustedProxies, true)) {
                return $candidate;
            }
        }

        return $remoteAddr;
    }

    /**
     * @return list<string>
     */
    public static function trustedProxiesFromEnv(): array
    {
        $raw = getenv('TRUSTED_PROXIES') ?: ($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1');

        return array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
    }
}
