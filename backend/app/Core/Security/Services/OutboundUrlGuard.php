<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Services;

use RuntimeException;

/**
 * SSRF ochrana pre odchádzajúce HTTP(S) volania na admin-konfigurovateľné URL
 * (audit C14/S-SSRF): generic OAuth token/userinfo URL, ntfy server, webhook,
 * Discord webhook.
 *
 * Bez tejto kontroly by admin (alebo kompromitovaný admin/nastavenia) mohol
 * server prinútiť volať interné služby — cloud metadata (`169.254.169.254`),
 * `localhost`, privátne rozsahy (`10.*`, `192.168.*`, …) alebo ne-HTTPS ciele.
 *
 * Pravidlá (produkcia):
 *  - povolené len `https://` (v dev/test aj `http://`),
 *  - zakázané userinfo v URL (`https://user:pass@host` – SSRF trik),
 *  - host sa rozloží na IP a odmietne, ak leží v privátnom/rezervovanom rozsahu
 *    (vrátane loopback a link-local metadata), fail-closed pri nerozlíšiteľnom hoste.
 *
 * V `testing`/`development`/`local` je guard zámerne uvoľnený (povolené http aj
 * privátne ciele), aby fungoval lokálny vývoj (napr. SSO/ntfy na `localhost`).
 */
final class OutboundUrlGuard
{
    public function __construct(
        private bool $allowHttp = false,
        private bool $allowPrivate = false
    ) {
    }

    public static function fromEnv(): self
    {
        $env = (string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production'));
        $relaxed = in_array($env, ['testing', 'test', 'development', 'local'], true);

        return new self($relaxed, $relaxed);
    }

    public function isAllowed(string $url): bool
    {
        try {
            $this->assertAllowed($url);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * @throws RuntimeException ak URL nie je bezpečná pre odchádzajúce volanie
     */
    public function assertAllowed(string $url): void
    {
        $url = trim($url);
        if ($url === '') {
            throw new RuntimeException('Empty outbound URL');
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new RuntimeException('Malformed outbound URL: ' . $url);
        }

        $scheme = strtolower($parts['scheme']);
        $allowedSchemes = $this->allowHttp ? ['https', 'http'] : ['https'];
        if (!in_array($scheme, $allowedSchemes, true)) {
            throw new RuntimeException('Blocked URL scheme: ' . $scheme);
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('Userinfo is not allowed in outbound URL');
        }

        if ($this->allowPrivate) {
            return;
        }

        $host = trim((string) $parts['host'], '[]');
        foreach ($this->resolveIps($host) as $ip) {
            if (!$this->isPublicIp($ip)) {
                throw new RuntimeException('Blocked private/reserved outbound host: ' . $host);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function resolveIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ips = [];

        $v4 = @gethostbynamel($host);
        if (is_array($v4)) {
            foreach ($v4 as $ip) {
                $ips[] = $ip;
            }
        }

        $records = @dns_get_record($host, DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        if ($ips === []) {
            // Fail-closed: neznámy/nerozlíšiteľný host v produkcii neprepustíme.
            throw new RuntimeException('Cannot resolve outbound host: ' . $host);
        }

        return $ips;
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
