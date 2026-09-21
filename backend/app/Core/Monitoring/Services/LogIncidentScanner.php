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
    /** Do not email log rows older than this; mark them notified so scans do not loop. */
    private const INCIDENT_EMAIL_MAX_AGE_SEC = 172800;

    /** Same route/severity (e.g. desk 500 every 30s) → at most one email per cooldown window. */
    private const INCIDENT_FINGERPRINT_COOLDOWN_SEC = 86400;

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
        $notifiedFingerprints = [];
        /** @var array<string, true> */
        $fingerprintsSeenThisScan = [];
        $notifiedCount = 0;

        foreach ($entries as $entry) {
            $id = (string) ($entry['id'] ?? '');
            if ($id === '' || isset($alreadyNotified[$id])) {
                continue;
            }

            $entryTs = strtotime((string) ($entry['timestamp'] ?? ''));
            if ($entryTs !== false && $entryTs < time() - self::INCIDENT_EMAIL_MAX_AGE_SEC) {
                $notifiedIds[] = $id;
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

            $fingerprint = $this->entryFingerprint($entry, $severity);
            if (
                isset($fingerprintsSeenThisScan[$fingerprint])
                || $this->state->isLogFingerprintInCooldown($fingerprint, self::INCIDENT_FINGERPRINT_COOLDOWN_SEC)
            ) {
                $notifiedIds[] = $id;
                continue;
            }
            $fingerprintsSeenThisScan[$fingerprint] = true;

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
                $notifiedFingerprints[] = $fingerprint;
                ++$notifiedCount;
            }
        }

        $stateError = null;
        try {
            if ($notifiedIds !== []) {
                $this->state->addNotifiedLogIds($notifiedIds);
            }
            if ($notifiedFingerprints !== []) {
                $this->state->markLogFingerprints($notifiedFingerprints);
            }

            $this->state->setLastLogScanAt(date('Y-m-d H:i:s'));
        } catch (\Throwable $e) {
            $stateError = $e->getMessage();
        }

        $result = ['notified' => $notifiedCount, 'scanned' => count($entries)];
        if ($stateError !== null) {
            $result['state_persisted'] = false;
            $result['state_error'] = $stateError;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function entryFingerprint(array $entry, string $severity): string
    {
        $category = (string) ($entry['category'] ?? 'app');
        $context = is_array($entry['context'] ?? null) ? $entry['context'] : [];
        $path = (string) ($context['path'] ?? '');
        $status = (string) ($context['status'] ?? '');

        $message = (string) ($entry['message'] ?? '');
        if ($path === '' && preg_match('#\s(/api/\S+)\s+(\d{3})\s*$#', $message, $matches) === 1) {
            $path = $matches[1];
            if ($status === '') {
                $status = $matches[2];
            }
        }

        return hash('sha256', $category . '|' . $path . '|' . $severity . '|' . $status);
    }
}
