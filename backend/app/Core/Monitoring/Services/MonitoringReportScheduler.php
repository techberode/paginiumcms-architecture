<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Monitoring\Services;

use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Sends scheduled monitoring reports via configured connector (Iteration 7).
 */
final class MonitoringReportScheduler
{
    /** @var array<string, int> */
    private const WEEKDAY_MAP = [
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6,
        'sun' => 7,
    ];

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private MonitoringReportBuilder $builder,
        private IncidentNotifier $notifier,
        private SchedulerStateStore $state
    ) {
    }

    public function isDue(): bool
    {
        $monitoring = $this->settings->group('monitoring');
        if (!(bool) ($monitoring['reportsEnabled'] ?? false)) {
            return false;
        }

        $interval = (string) ($monitoring['reportInterval'] ?? 'day');
        $timezone = (string) ($this->settings->group('general')['timezone'] ?? 'Europe/Bratislava');

        try {
            $now = new \DateTimeImmutable('now', new \DateTimeZone($timezone));
        } catch (\Throwable) {
            $now = new \DateTimeImmutable('now');
        }

        $lastReportAt = $this->state->getLastReportAt();

        return match ($interval) {
            'hour' => $this->isHourlyDue($now, $monitoring, $lastReportAt),
            'week' => $this->isWeeklyDue($now, $monitoring, $lastReportAt),
            default => $this->isDailyDue($now, $monitoring, $lastReportAt),
        };
    }

    /**
     * @return array{sent: bool, connector: string, reason?: string}
     */
    public function runIfDue(bool $force = false): array
    {
        if (!$force && !$this->isDue()) {
            return ['sent' => false, 'connector' => '', 'reason' => 'not_due'];
        }

        $monitoring = $this->settings->group('monitoring');
        if (!(bool) ($monitoring['reportsEnabled'] ?? false) && !$force) {
            return ['sent' => false, 'connector' => '', 'reason' => 'disabled'];
        }

        $interval = (string) ($monitoring['reportInterval'] ?? 'day');
        $connector = (string) ($monitoring['reportConnector'] ?? 'email');
        $payload = $this->builder->build($interval);

        $delivery = $this->notifier->notifyViaConnectorDetailed(
            $connector,
            'monitoring.scheduled_report',
            $payload['subject'],
            $payload['body'],
            'info',
            ['html' => $payload['html']]
        );

        if ($delivery['sent']) {
            $this->state->setLastReportAt(date('c'));

            return [
                'sent' => true,
                'connector' => $connector,
            ];
        }

        return [
            'sent' => false,
            'connector' => $connector,
            'reason' => $delivery['reason'] ?? 'delivery_failed',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schedulePreview(): array
    {
        $monitoring = $this->settings->group('monitoring');

        return [
            'enabled' => (bool) ($monitoring['reportsEnabled'] ?? false),
            'interval' => (string) ($monitoring['reportInterval'] ?? 'day'),
            'time' => (string) ($monitoring['reportTime'] ?? '08:00'),
            'minute' => max(0, min(59, (int) ($monitoring['reportMinute'] ?? 0))),
            'weekday' => (string) ($monitoring['reportWeekday'] ?? 'mon'),
            'connector' => (string) ($monitoring['reportConnector'] ?? 'email'),
            'last_sent_at' => $this->state->getLastReportAt(),
            'due_now' => $this->isDue(),
        ];
    }

    /**
     * @param array<string, mixed> $monitoring
     */
    private function isHourlyDue(\DateTimeImmutable $now, array $monitoring, ?string $lastReportAt): bool
    {
        $targetMinute = max(0, min(59, (int) ($monitoring['reportMinute'] ?? 0)));
        if ((int) $now->format('i') !== $targetMinute) {
            return false;
        }

        if ($lastReportAt === null) {
            return true;
        }

        $last = strtotime($lastReportAt);
        if ($last === false) {
            return true;
        }

        return $now->format('Y-m-d H') !== date('Y-m-d H', $last);
    }

    /**
     * @param array<string, mixed> $monitoring
     */
    private function isDailyDue(\DateTimeImmutable $now, array $monitoring, ?string $lastReportAt): bool
    {
        [$hour, $minute] = $this->parseTime((string) ($monitoring['reportTime'] ?? '08:00'));
        if ((int) $now->format('H') !== $hour || (int) $now->format('i') !== $minute) {
            return false;
        }

        if ($lastReportAt === null) {
            return true;
        }

        $last = strtotime($lastReportAt);
        if ($last === false) {
            return true;
        }

        return $now->format('Y-m-d') !== date('Y-m-d', $last);
    }

    /**
     * @param array<string, mixed> $monitoring
     */
    private function isWeeklyDue(\DateTimeImmutable $now, array $monitoring, ?string $lastReportAt): bool
    {
        $weekdayKey = strtolower((string) ($monitoring['reportWeekday'] ?? 'mon'));
        $targetIso = self::WEEKDAY_MAP[$weekdayKey] ?? 1;
        if ((int) $now->format('N') !== $targetIso) {
            return false;
        }

        return $this->isDailyDue($now, $monitoring, $lastReportAt);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseTime(string $time): array
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($time), $matches)) {
            return [8, 0];
        }

        return [max(0, min(23, (int) $matches[1])), max(0, min(59, (int) $matches[2]))];
    }
}
