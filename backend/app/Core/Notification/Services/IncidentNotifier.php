<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Services;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Dispatches incident alerts to enabled connectors (Iteration 6).
 */
final class IncidentNotifier
{
    private const SEVERITY_RANK = ['info' => 0, 'warning' => 1, 'error' => 2, 'critical' => 3];
    private const DEFAULT_ALERT_COOLDOWN_SECONDS = 900;

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private NotificationService $notifications,
        private CacheManager $cache
    ) {
    }

    public function notify(string $event, string $subject, string $message, string $severity = 'warning'): void
    {
        $monitoring = $this->settings->group('monitoring');
        if (!(bool) ($monitoring['alertsEnabled'] ?? false)) {
            return;
        }

        $minSeverity = (string) ($monitoring['minSeverity'] ?? 'warning');
        if ((self::SEVERITY_RANK[$severity] ?? 0) < (self::SEVERITY_RANK[$minSeverity] ?? 1)) {
            return;
        }

        $general = $this->settings->group('general');
        $to = (string) ($monitoring['alertEmail'] ?? '');
        if ($to === '') {
            $to = (string) ($general['adminEmail'] ?? '');
        }

        $options = [
            'event' => $event,
            'severity' => $severity,
            'meta' => ['source' => 'paginiumcms'],
        ];

        foreach ($this->notifications->getAdapters() as $adapter) {
            try {
                $this->notifications->send($adapter, $to, $subject, $message, $options);
            } catch (\Throwable) {
                // best-effort multi-channel delivery
            }
        }
    }

    /**
     * @deprecated Prefer notifyLoginLockout() – sends at most once per cooldown on lockout.
     */
    public function notifyFailedLogin(string $email, string $ip): void
    {
        $this->notifyLoginLockout($email, $ip);
    }

    /**
     * Alert after brute-force lockout (not on every single failed attempt).
     */
    public function notifyLoginLockout(string $email, string $ip): void
    {
        $monitoring = $this->settings->group('monitoring');
        if (!(bool) ($monitoring['notifyFailedLogin'] ?? true)) {
            return;
        }

        if ($this->shouldSkipSecurityAlert($email)) {
            return;
        }

        $cooldown = max(60, (int) ($monitoring['failedLoginAlertCooldownSeconds'] ?? self::DEFAULT_ALERT_COOLDOWN_SECONDS));
        $dedupeKey = 'auth.lockout:' . mb_strtolower(trim($email)) . ':' . $ip;
        if (!$this->shouldSendThrottled($dedupeKey, $cooldown)) {
            return;
        }

        $this->notify(
            'auth.failed_login',
            'Login lockout triggered',
            sprintf(
                'Repeated failed login for %s from IP %s at %s. Account/IP temporarily locked.',
                $email,
                $ip,
                date('c')
            ),
            'warning'
        );
    }

    public function notifySecurityEvent(string $action, string $details, string $severity = 'warning'): void
    {
        $monitoring = $this->settings->group('monitoring');
        if (!(bool) ($monitoring['notifySecurityIncident'] ?? true)) {
            return;
        }

        $this->notify('audit.security', 'Security: ' . $action, $details, $severity);
    }

    /**
     * @param array<string, mixed> $extraOptions
     */
    public function notifyViaConnector(
        string $connector,
        string $event,
        string $subject,
        string $message,
        string $severity = 'info',
        array $extraOptions = []
    ): bool {
        return $this->notifyViaConnectorDetailed($connector, $event, $subject, $message, $severity, $extraOptions)['sent'];
    }

    /**
     * @param array<string, mixed> $extraOptions
     * @return array{sent: bool, reason?: string}
     */
    public function notifyViaConnectorDetailed(
        string $connector,
        string $event,
        string $subject,
        string $message,
        string $severity = 'info',
        array $extraOptions = []
    ): array {
        $preflight = $this->deliveryPreflight($connector);
        if ($preflight !== null) {
            return ['sent' => false, 'reason' => $preflight];
        }

        $general = $this->settings->group('general');
        $monitoring = $this->settings->group('monitoring');
        $to = (string) ($monitoring['alertEmail'] ?? '');
        if ($to === '') {
            $to = (string) ($general['adminEmail'] ?? '');
        }

        $options = array_merge([
            'event' => $event,
            'severity' => $severity,
            'meta' => ['source' => 'paginiumcms'],
        ], $extraOptions);

        if ($connector === 'all') {
            $adapters = $this->notifications->getAdapters();
            $any = false;
            foreach ($adapters as $adapter) {
                try {
                    $any = $this->notifications->send($adapter, $to, $subject, $message, $options) || $any;
                } catch (\Throwable) {
                    // best-effort
                }
            }

            if ($any) {
                return ['sent' => true];
            }

            return ['sent' => false, 'reason' => 'delivery_failed'];
        }

        try {
            $sent = $this->notifications->send($connector, $to, $subject, $message, $options);

            if ($sent) {
                return ['sent' => true];
            }

            return ['sent' => false, 'reason' => 'delivery_failed'];
        } catch (\Throwable) {
            return ['sent' => false, 'reason' => 'delivery_failed'];
        }
    }

    public function deliveryPreflight(string $connector): ?string
    {
        $general = $this->settings->group('general');
        $monitoring = $this->settings->group('monitoring');
        $to = (string) ($monitoring['alertEmail'] ?? '');
        if ($to === '') {
            $to = (string) ($general['adminEmail'] ?? '');
        }

        $active = $this->notifications->getAdapters();

        if ($connector === 'all') {
            return $active === [] ? 'no_connectors' : null;
        }

        if (!in_array($connector, $active, true)) {
            return 'connector_inactive';
        }

        if ($connector === 'email' && $to === '') {
            return 'missing_recipient';
        }

        return null;
    }

    private function shouldSkipSecurityAlert(string $email): bool
    {
        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '');
        if ($env === 'testing') {
            return true;
        }

        $normalized = mb_strtolower(trim($email));
        if ($normalized === '') {
            return false;
        }

        return str_ends_with($normalized, '@example.com')
            || str_ends_with($normalized, '@example.org')
            || str_starts_with($normalized, 'test_');
    }

    private function shouldSendThrottled(string $dedupeKey, int $cooldownSeconds): bool
    {
        $cacheKey = 'incident.cooldown.' . md5($dedupeKey);
        if ($this->cache->has($cacheKey)) {
            return false;
        }

        $this->cache->set($cacheKey, time(), $cooldownSeconds);

        return true;
    }
}
