<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\Logging\Models\LogEntry;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Writes structured HTTP access logs with timestamp + IP on every entry.
 */
final class AccessLogService
{
    /** @var list<string> */
    private const SENSITIVE_PATHS = [
        '/api/auth/login',
        '/api/auth/register',
        '/api/auth/change-password',
        '/api/auth/reset-password',
        '/api/auth/verify-reset-token',
        '/api/auth/2fa/verify-login',
    ];

    /**
     * High-churn / meta endpoints that drown real access signals in Admin → Logs.
     *
     * @var list<string>
     */
    private const EXCLUDED_PATH_PREFIXES = [
        '/api/health',
        '/api/debug/client-event',
        '/api/admin/logs',
    ];

    public function __construct(
        private LogWriterInterface $writer,
        private SettingsRepositoryInterface $settings
    ) {
    }

    public function isEnabled(): bool
    {
        $logging = $this->settings->group('logging');

        return (bool) ($logging['enabled'] ?? true) && (bool) ($logging['requestLogging'] ?? true);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function logRequest(
        string $ip,
        string $method,
        string $path,
        int $status,
        float $durationMs,
        ?string $userId = null,
        array $context = []
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        if ($this->isExcludedPath($path)) {
            return;
        }

        $logging = $this->settings->group('logging');
        $minSeverity = (string) ($logging['minSeverity'] ?? LogSeverity::DEBUG);
        $slowMs = max(0, (int) ($logging['slowRequestMs'] ?? 2000));

        $severity = $this->severityForStatus($status, $durationMs, $slowMs);
        if (!$this->passesMinSeverity($severity, $minSeverity)) {
            return;
        }

        if ($this->isSensitivePath($path) && !((bool) ($logging['logAuthEndpoints'] ?? false))) {
            return;
        }

        // Anti log-injection (C11): path a user-controlled kontext (query,
        // user_agent, …) sa pred zápisom očistia od CR/LF a control znakov.
        $path = LogSanitizer::value($path, 2048);

        $message = sprintf('%s %s %d', strtoupper($method), $path, $status);
        $entry = new LogEntry($severity, 'http_access', $message);
        $entry->setIp($ip);

        if ($userId !== null && $userId !== '') {
            $entry->setUserId($userId);
        }

        $sizeContext = $this->responseSizeContext($context);

        $entry->setContext(array_merge([
            'method' => strtoupper($method),
            'path' => $path,
            'status' => $status,
            'duration_ms' => round($durationMs, 2),
            'timestamp_utc' => gmdate('c'),
        ], $sizeContext, LogSanitizer::context($context)));

        $this->writer->write($entry);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, int|null>
     */
    private function responseSizeContext(array &$context): array
    {
        if (!$this->includeResponseSize() || !array_key_exists('size_bytes', $context)) {
            return [];
        }

        $sizeBytes = $context['size_bytes'];
        unset($context['size_bytes']);

        if ($sizeBytes !== null && !is_int($sizeBytes)) {
            return [];
        }

        return ['size_bytes' => $sizeBytes];
    }

    private function includeResponseSize(): bool
    {
        $logging = $this->settings->group('logging');

        return (bool) ($logging['includeResponseSize'] ?? true);
    }

    public function purgeOldLogs(): int
    {
        $logging = $this->settings->group('logging');
        $days = max(1, (int) ($logging['retentionDays'] ?? 30));

        return $this->writer->clearOld($days);
    }

    private function severityForStatus(int $status, float $durationMs, int $slowMs): string
    {
        if ($status >= 500) {
            return LogSeverity::ERROR;
        }
        // Expected "not found" / anonymous auth probe — operational noise as WARNING.
        if ($status === 404 || $status === 401) {
            return LogSeverity::INFO;
        }
        if ($status >= 400) {
            return LogSeverity::WARNING;
        }
        if ($slowMs > 0 && $durationMs >= $slowMs) {
            return LogSeverity::WARNING;
        }
        if ($status >= 300) {
            return LogSeverity::DEBUG;
        }

        return LogSeverity::INFO;
    }

    private function passesMinSeverity(string $severity, string $minSeverity): bool
    {
        if (!LogSeverity::isValid($minSeverity)) {
            return true;
        }

        $levels = [
            LogSeverity::DEBUG => 0,
            LogSeverity::INFO => 1,
            LogSeverity::WARNING => 2,
            LogSeverity::ERROR => 3,
            LogSeverity::CRITICAL => 4,
        ];

        return ($levels[$severity] ?? 0) >= ($levels[$minSeverity] ?? 0);
    }

    private function isSensitivePath(string $path): bool
    {
        foreach (self::SENSITIVE_PATHS as $sensitive) {
            if (str_starts_with($path, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    private function isExcludedPath(string $path): bool
    {
        foreach (self::EXCLUDED_PATH_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
