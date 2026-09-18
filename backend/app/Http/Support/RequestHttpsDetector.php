<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

/**
 * Detects whether the inbound admin/API request is served over HTTPS (direct or trusted proxy).
 */
final class RequestHttpsDetector
{
    /**
     * @param array<string, mixed> $server
     * @param list<string> $trustedProxies
     * @return array{secure: bool, source: string}
     */
    public static function detect(array $server, array $trustedProxies = []): array
    {
        if (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') {
            return ['secure' => true, 'source' => 'server_https'];
        }

        $remoteAddr = (string) ($server['REMOTE_ADDR'] ?? '');
        $forwardedRaw = (string) ($server['HTTP_X_FORWARDED_PROTO'] ?? '');
        $forwarded = strtolower(trim(strtok($forwardedRaw, ',') ?: ''));

        if ($forwarded === 'https') {
            if ($trustedProxies === [] || in_array($remoteAddr, $trustedProxies, true)) {
                return ['secure' => true, 'source' => 'x_forwarded_proto'];
            }

            return ['secure' => false, 'source' => 'untrusted_forwarded_proto'];
        }

        if ($forwarded === 'http' && ($trustedProxies === [] || in_array($remoteAddr, $trustedProxies, true))) {
            return ['secure' => false, 'source' => 'x_forwarded_proto_http'];
        }

        return ['secure' => false, 'source' => 'none'];
    }

    /**
     * @param array<string, mixed> $server
     * @param list<string> $trustedProxies
     */
    public static function isSecure(array $server, array $trustedProxies = []): bool
    {
        return self::detect($server, $trustedProxies)['secure'];
    }
}
