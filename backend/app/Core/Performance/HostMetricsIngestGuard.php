<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use PaginiumCMS\Core\Security\ClientIpResolver;

final class HostMetricsIngestGuard
{
    public function __construct(
        private HostMetricsSettings $settings
    ) {
    }

    /**
     * @param array<string, mixed>|null $serverParams
     */
    public function assertAllowed(?array $serverParams, string $providedToken): void
    {
        if (!$this->settings->enabled()) {
            throw new HostMetricsIngestException('host_metrics_disabled', 403);
        }

        $expected = $this->settings->ingestToken();
        if ($expected === '') {
            throw new HostMetricsIngestException('host_metrics_token_not_configured', 503);
        }

        if (!hash_equals($expected, $providedToken)) {
            throw new HostMetricsIngestException('host_metrics_token_invalid', 403);
        }

        $server = $serverParams ?? $_SERVER;
        $ip = ClientIpResolver::resolve($server, ClientIpResolver::trustedProxiesFromEnv());
        if (!$this->isLocalOrPrivate($ip)) {
            throw new HostMetricsIngestException('host_metrics_ingest_ip_denied', 403);
        }
    }

    private function isLocalOrPrivate(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
