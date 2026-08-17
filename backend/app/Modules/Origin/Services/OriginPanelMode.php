<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Services;

use PaginiumCMS\Modules\Demo\Services\DemoMode;

/**
 * Maintainer-only Origin Panel gate (It.82a).
 *
 * Active only when ORIGIN_PANEL=true, host is allowlisted, and demo mode is off.
 */
final class OriginPanelMode
{
    private static bool $misconfigurationLogged = false;

    public function isEnabled(): bool
    {
        return self::isActive(null);
    }

    public static function isEnabledFromEnv(): bool
    {
        return filter_var(
            getenv('ORIGIN_PANEL') ?: ($_ENV['ORIGIN_PANEL'] ?? false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public static function isActive(?string $requestHost): bool
    {
        if (!self::isEnabledFromEnv()) {
            return false;
        }

        if (DemoMode::isEnabledFromEnv()) {
            return false;
        }

        $host = self::normalizeHost($requestHost ?? self::hostFromAppUrl());
        if ($host === '') {
            return false;
        }

        if (self::isDevelopmentEnvironment() && self::isLocalOrPrivateHost($host)) {
            return true;
        }

        return self::isAllowedHost($host);
    }

    public static function isAllowedHost(string $host): bool
    {
        $normalized = self::normalizeHost($host);
        if ($normalized === '') {
            return false;
        }

        foreach (self::allowedHosts() as $allowed) {
            if ($normalized === $allowed) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function allowedHosts(): array
    {
        $hosts = [];

        $raw = trim((string) (getenv('ORIGIN_PANEL_ALLOWED_HOSTS') ?: ($_ENV['ORIGIN_PANEL_ALLOWED_HOSTS'] ?? '')));
        if ($raw !== '') {
            $hosts = array_values(array_filter(array_map(
                static fn (string $part): string => self::normalizeHost($part),
                explode(',', $raw)
            )));
        } else {
            $hosts = ['localhost', '127.0.0.1', '::1', 'paginiumcms.com'];
        }

        $appHost = self::hostFromAppUrl();
        if ($appHost !== '' && !in_array($appHost, $hosts, true)) {
            $hosts[] = $appHost;
        }

        return array_values(array_unique($hosts));
    }

    public static function warnIfMisconfigured(): void
    {
        if (self::$misconfigurationLogged || !self::isEnabledFromEnv()) {
            return;
        }

        if (DemoMode::isEnabledFromEnv()) {
            self::$misconfigurationLogged = true;
            error_log(
                '[PaginiumCMS][ORIGIN] ORIGIN_PANEL=true is ignored when DEMO_MODE is active (fail-closed).'
            );

            return;
        }

        $appHost = self::hostFromAppUrl();
        if ($appHost !== '' && !self::isAllowedHost($appHost)) {
            self::$misconfigurationLogged = true;
            error_log(
                '[PaginiumCMS][ORIGIN] ORIGIN_PANEL=true but APP_URL host is not allowlisted (fail-closed). '
                . 'Set ORIGIN_PANEL_ALLOWED_HOSTS or fix APP_URL.'
            );
        }
    }

    private static function hostFromAppUrl(): string
    {
        $url = trim((string) (getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '')));
        if ($url === '') {
            return '';
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? self::normalizeHost($host) : '';
    }

    private static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return '';
        }

        if (str_starts_with($host, '[') && str_contains($host, ']')) {
            $host = substr($host, 1, (int) strpos($host, ']') - 1);
        }

        $portPos = strrpos($host, ':');
        if ($portPos !== false && str_contains(substr($host, 0, $portPos), '.')) {
            $host = substr($host, 0, $portPos);
        }

        return $host;
    }

    private static function isDevelopmentEnvironment(): bool
    {
        $appEnv = strtolower(trim((string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development'))));

        return in_array($appEnv, ['development', 'local', 'testing'], true);
    }

    private static function isLocalOrPrivateHost(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        return filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
