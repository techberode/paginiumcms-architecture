<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Services;

use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Dispatches incident alerts to enabled connectors (Iteration 6).
 */
final class IncidentNotifier
{
    private const SEVERITY_RANK = ['info' => 0, 'warning' => 1, 'error' => 2, 'critical' => 3];

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private NotificationService $notifications
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

    public function notifyFailedLogin(string $email, string $ip): void
    {
        $monitoring = $this->settings->group('monitoring');
        if (!(bool) ($monitoring['notifyFailedLogin'] ?? true)) {
            return;
        }

        $this->notify(
            'auth.failed_login',
            'Failed login attempt',
            sprintf('Failed login for %s from IP %s at %s', $email, $ip, date('c')),
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
}
