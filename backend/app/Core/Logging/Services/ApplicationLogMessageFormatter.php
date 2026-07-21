<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\AuditTrail\Services\AuditMessageFormatter;
use PaginiumCMS\Core\Logging\Models\LogSeverity;

/**
 * Ľudsky čitateľné správy pre admin sekciu Logy.
 */
final class ApplicationLogMessageFormatter
{
    /** @var array<string, string> */
    private const PATH_LABELS = [
        '/api/admin/logs/stats' => 'štatistiky logov',
        '/api/admin/logs' => 'zoznam logov',
        '/api/admin/dashboard/overview' => 'prehľad dashboardu',
        '/api/admin/audit/stats' => 'audit štatistiky',
        '/api/admin/audit/export' => 'export audit trailu',
        '/api/admin/users' => 'správa používateľov',
        '/api/admin/backups' => 'zálohy',
        '/api/pages' => 'zoznam stránok',
        '/api/articles' => 'zoznam článkov',
        '/api/media' => 'médiá',
        '/api/auth/login' => 'prihlásenie',
        '/api/auth/logout' => 'odhlásenie',
        '/api/auth/me' => 'overenie session',
        '/api/debug/client-event' => 'debug udalosť z frontendu',
        '/api/settings/public' => 'verejné nastavenia',
    ];

    /** @var array<int, string> */
    private const STATUS_LABELS = [
        200 => 'OK',
        201 => 'Vytvorené',
        204 => 'Bez obsahu',
        301 => 'Presmerovanie',
        302 => 'Presmerovanie',
        400 => 'Neplatná požiadavka',
        401 => 'Neautorizované',
        403 => 'Zakázané',
        404 => 'Nenájdené',
        409 => 'Konflikt',
        422 => 'Validačná chyba',
        429 => 'Rate limit',
        500 => 'Chyba servera',
        503 => 'Služba nedostupná',
    ];

    public function __construct(
        private AuditMessageFormatter $auditFormatter = new AuditMessageFormatter()
    ) {
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function format(array $entry): string
    {
        $display = $entry['display_message'] ?? null;
        if (is_string($display) && trim($display) !== '') {
            return $display;
        }

        $category = (string) ($entry['category'] ?? '');
        $context = is_array($entry['context'] ?? null) ? $entry['context'] : [];

        if ($category === 'http_access') {
            return $this->formatHttpAccess($entry, $context);
        }

        if (str_starts_with($category, 'audit_') || $this->isAuditAppEntry($entry, $context)) {
            return $this->auditFormatter->formatFromLog($entry);
        }

        $summary = $context['summary'] ?? null;
        if (is_string($summary) && trim($summary) !== '') {
            return $summary;
        }

        $message = trim((string) ($entry['message'] ?? ''));
        if ($message !== '') {
            return $this->prefixWithSeverity((string) ($entry['severity'] ?? LogSeverity::INFO), $message);
        }

        return $this->prefixWithSeverity((string) ($entry['severity'] ?? LogSeverity::INFO), 'Systémová udalosť');
    }

    /**
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    public function enrich(array $entry): array
    {
        $entry['display_message'] = $this->format($entry);
        $entry['severity'] = strtolower((string) ($entry['severity'] ?? LogSeverity::INFO));

        return $entry;
    }

    /**
     * @param array<string, mixed> $entry
     * @param array<string, mixed> $context
     */
    private function formatHttpAccess(array $entry, array $context): string
    {
        $method = strtoupper((string) ($context['method'] ?? 'GET'));
        $path = (string) ($context['path'] ?? '/');
        $status = (int) ($context['status'] ?? 0);
        $durationMs = (float) ($context['duration_ms'] ?? 0);
        $severity = strtoupper((string) ($entry['severity'] ?? LogSeverity::INFO));
        $pathLabel = self::PATH_LABELS[$path] ?? $this->humanizePath($path);
        $statusLabel = self::STATUS_LABELS[$status] ?? (string) $status;

        $detail = sprintf('%s %s → %d %s', $method, $path, $status, $statusLabel);
        if ($durationMs > 0) {
            $detail .= sprintf(' (%.0f ms)', $durationMs);
        }

        return match ($severity) {
            LogSeverity::CRITICAL, LogSeverity::ERROR => sprintf(
                'Chyba servera pri „%s“: %s',
                $pathLabel,
                $detail
            ),
            LogSeverity::WARNING => $status >= 400
                ? sprintf('Varovanie pri „%s“: %s', $pathLabel, $detail)
                : sprintf('Pomalá odpoveď pri „%s“: %s', $pathLabel, $detail),
            LogSeverity::DEBUG => sprintf('HTTP presmerovanie / debug: %s', $detail),
            default => sprintf('Úspešný prístup k „%s“: %s', $pathLabel, $detail),
        };
    }

    private function prefixWithSeverity(string $severity, string $message): string
    {
        $severity = strtoupper($severity);

        return match ($severity) {
            LogSeverity::CRITICAL => 'Kritická udalosť: ' . $message,
            LogSeverity::ERROR => 'Chyba: ' . $message,
            LogSeverity::WARNING => 'Varovanie: ' . $message,
            LogSeverity::DEBUG => 'Debug: ' . $message,
            default => $message,
        };
    }

    /**
     * @param array<string, mixed> $entry
     * @param array<string, mixed> $context
     */
    private function isAuditAppEntry(array $entry, array $context): bool
    {
        if (($context['category'] ?? null) !== null) {
            return in_array($context['category'], ['content_change', 'content_access', 'admin_action', 'security'], true);
        }

        return preg_match('/\[(CONTENT_CHANGE|CONTENT_ACCESS|ADMIN_ACTION|SECURITY)\]/', (string) ($entry['message'] ?? '')) === 1;
    }

    private function humanizePath(string $path): string
    {
        $trimmed = trim($path, '/');
        if ($trimmed === '') {
            return 'domovská stránka API';
        }

        $segments = explode('/', $trimmed);
        if ($segments[0] === 'api') {
            array_shift($segments);
        }

        return str_replace(['-', '_'], ' ', implode(' / ', $segments));
    }
}
