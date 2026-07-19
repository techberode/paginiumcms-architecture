<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security;

/**
 * Resolves the client IP behind optional trusted reverse proxies.
 */
final class ClientIpResolver
{
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

        $parts = array_map('trim', explode(',', $forwardedFor));
        $clientIp = $parts[0];

        return filter_var($clientIp, FILTER_VALIDATE_IP) ? $clientIp : $remoteAddr;
    }

    /**
     * @return list<string>
     */
    public static function trustedProxiesFromEnv(): array
    {
        $raw = getenv('TRUSTED_PROXIES') ?: ($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1,192.168.10.26');

        return array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
    }
}
