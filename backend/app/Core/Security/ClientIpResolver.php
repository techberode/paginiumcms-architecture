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
        $forwardedFor = (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '');

        return self::resolveFromForwardedFor($remoteAddr, $forwardedFor, $trustedProxies);
    }

    /**
     * Single implementation of the trusted-proxy walk (used by resolve() and HTTP middlewares).
     *
     * `X-Forwarded-For` is honoured only when the direct peer is a trusted proxy. The list is then
     * walked from the RIGHT (the hop closest to us) and the first address that is NOT a trusted proxy
     * is the client. Taking the leftmost entry is unsafe: a proxy using `$proxy_add_x_forwarded_for`
     * only appends, so the leftmost value is whatever the client chose to send.
     * Malformed entries fail closed to the direct peer address.
     *
     * @param list<string> $trustedProxies
     */
    public static function resolveFromForwardedFor(string $remoteAddr, string $forwardedFor, array $trustedProxies): string
    {
        if ($trustedProxies === [] || $forwardedFor === '' || !in_array($remoteAddr, $trustedProxies, true)) {
            return $remoteAddr;
        }

        foreach (array_reverse(array_map('trim', explode(',', $forwardedFor))) as $candidate) {
            if ($candidate === '') {
                continue;
            }
            if (filter_var($candidate, FILTER_VALIDATE_IP) === false) {
                return $remoteAddr;
            }
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
