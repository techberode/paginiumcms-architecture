<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Monitoring\Services;

use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Scans application logs and dispatches incident notifications (Iteration 7).
 */
final class LogIncidentScanner
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private LogWriterInterface $logWriter,
        private IncidentNotifier $notifier,
        private SchedulerStateStore $state
    ) {
    }

    /**
     * @return array{notified: int, scanned: int}
     */
    public function scan(): array
    {
        $monitoring = $this->settings->group('monitoring');
        $notifyErrors = (bool) ($monitoring['notifyLogErrors'] ?? true);
        $notifyWarnings = (bool) ($monitoring['notifyLogWarnings'] ?? false);

        if (!$notifyErrors && !$notifyWarnings) {
            return ['notified' => 0, 'scanned' => 0];
        }

        $since = $this->state->getLastLogScanAt() ?? date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $entries = $this->logWriter->readSince($since, 200);
        $alreadyNotified = array_flip($this->state->getNotifiedLogIds());
        $connector = (string) ($monitoring['logIncidentConnector'] ?? 'all');

        $notifiedIds = [];
        $notifiedCount = 0;

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $id = (string) ($entry['id'] ?? '');
            if ($id === '' || isset($alreadyNotified[$id])) {
                continue;
            }

            $severity = strtoupper((string) ($entry['severity'] ?? LogSeverity::INFO));
            if ($severity === LogSeverity::WARNING && !$notifyWarnings) {
                continue;
            }
            if (in_array($severity, [LogSeverity::ERROR, LogSeverity::CRITICAL], true) && !$notifyErrors) {
                continue;
            }
            if (!in_array($severity, [LogSeverity::WARNING, LogSeverity::ERROR, LogSeverity::CRITICAL], true)) {
                continue;
            }

            $message = (string) ($entry['message'] ?? 'Log event');
            $category = (string) ($entry['category'] ?? 'app');
            $subject = sprintf('Log %s: %s', strtolower($severity), $category);
            $body = sprintf(
                "%s\nCategory: %s\nTime: %s\n\n%s",
                $severity,
                $category,
                (string) ($entry['timestamp'] ?? date('c')),
                $message
            );

            $sent = $this->notifier->notifyViaConnector(
                $connector,
                'log.' . strtolower($severity),
                $subject,
                $body,
                strtolower($severity)
            );

            if ($sent) {
                $notifiedIds[] = $id;
                ++$notifiedCount;
            }
        }

        if ($notifiedIds !== []) {
            $this->state->addNotifiedLogIds($notifiedIds);
        }

        $this->state->setLastLogScanAt(date('Y-m-d H:i:s'));

        return ['notified' => $notifiedCount, 'scanned' => count($entries)];
    }
}
