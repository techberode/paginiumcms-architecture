<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Monitoring\Services;

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Health\Models\HealthStatus;
use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Builds monitoring reports – dark HTML email + plain text (Iteration 7).
 */
final class MonitoringReportBuilder
{
    private const BG = '#070b14';
    private const PANEL = '#0f172a';
    private const HEADER = '#0c4a6e';
    private const TEXT = '#cbd5e1';
    private const MUTED = '#64748b';
    private const ACCENT = '#38bdf8';
    private const GREEN = '#4ade80';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private ReporterInterface $reporter,
        private HealthCheckManager $health,
        private FlatFileStatsCollector $flatFileStats
    ) {
    }

    /**
     * @return array{subject: string, body: string, html: string, sections: list<string>}
     */
    public function build(string $interval = 'day'): array
    {
        $monitoring = $this->settings->group('monitoring');
        $general = $this->settings->group('general');
        $siteName = (string) ($general['siteName'] ?? 'PaginiumCMS');
        $generatedAt = date('Y-m-d H:i:s');
        $period = $this->analyticsPeriod($interval);
        $periodLabel = $this->intervalLabel($interval);

        $sections = [];
        $lines = [
            'PaginiumCMS – monitoring report',
            'Web: ' . $siteName,
            'Obdobie: ' . $periodLabel,
            'Vygenerované: ' . $generatedAt,
            str_repeat('=', 48),
            '',
        ];

        $htmlSections = '';

        if ((bool) ($monitoring['reportIncludeAnalytics'] ?? true)) {
            $overview = $this->reporter->getAggregatedOverview($interval);
            $devices = $this->reporter->getDeviceStats($period);
            $ipStats = $this->reporter->getTopIpStats(15, $period);
            $topPages = $this->reporter->getTopPages(10, $period);
            $topArticles = $this->reporter->getTopArticles(10, $period);
            $topReferers = $this->reporter->getTopReferers(10, $period);

            $sections[] = 'analytics';
            $lines[] = '--- Prehľad návštevnosti ---';
            $lines[] = sprintf(
                'Návštevy: %d | Zobrazenia: %d | Unikátne: %d | Realtime: %d',
                (int) ($overview['visits'] ?? 0),
                (int) ($overview['page_views'] ?? 0),
                (int) ($overview['unique_visitors'] ?? 0),
                (int) ($overview['realtime_visitors'] ?? 0)
            );
            $lines[] = sprintf(
                'Mobile: %d | Desktop: %d | Tablet: %d',
                (int) ($devices['mobile'] ?? 0),
                (int) ($devices['desktop'] ?? 0),
                (int) ($devices['tablet'] ?? 0)
            );
            $lines[] = '';

            $htmlSections .= $this->renderSummarySection($overview, $devices, $periodLabel);
            $htmlSections .= $this->renderIpStatsSection($ipStats, $lines);
            $htmlSections .= $this->renderListSection('Top stránky', 'globe', $topPages, 'uri', 'views', 'views', $lines);
            $htmlSections .= $this->renderArticleSection($topArticles, $lines);
            $htmlSections .= $this->renderRefererSection($topReferers, $lines);
        }

        if ((bool) ($monitoring['reportIncludeHealth'] ?? true)) {
            $report = $this->health->run();
            $payload = $report->toArray();
            $checks = [];
            if (isset($payload['checks']) && is_array($payload['checks'])) {
                foreach ($payload['checks'] as $check) {
                    if (is_array($check)) {
                        $checks[] = $check;
                    }
                }
            }
            $sections[] = 'health';
            $lines[] = '--- Systémové informácie ---';
            $lines[] = 'Stav: ' . (string) ($payload['status'] ?? 'unknown');
            foreach ($checks as $check) {
                $name = (string) ($check['name'] ?? $check['check'] ?? 'check');
                $status = (string) ($check['status'] ?? 'unknown');
                $message = (string) ($check['message'] ?? '');
                $lines[] = sprintf('[%s] %s: %s', $status, $name, $message);
            }
            $lines[] = '';
            $htmlSections .= $this->renderHealthSection((string) ($payload['status'] ?? 'unknown'), $checks);
        }

        if ((bool) ($monitoring['reportIncludeFlatFile'] ?? true)) {
            $stats = $this->flatFileStats->collect();
            $sections[] = 'flatfile';
            $lines[] = '--- PaginiumCMS flat-file ---';
            foreach ($stats as $key => $value) {
                $lines[] = sprintf('%s: %s', str_replace('_', ' ', (string) $key), (string) $value);
            }
            $lines[] = '';
            $htmlSections .= $this->renderFlatFileSection($stats);
        }

        $subject = sprintf('[%s] Monitoring report (%s)', $siteName, $interval);
        $plainBody = implode("\n", $lines);
        $html = $this->wrapHtmlDocument($siteName, $periodLabel, $generatedAt, $htmlSections);

        return [
            'subject' => $subject,
            'body' => $plainBody,
            'html' => $html,
            'sections' => $sections,
        ];
    }

    /**
     * @param array<string, mixed> $overview
     * @param array<string, int> $devices
     */
    private function renderSummarySection(array $overview, array $devices, string $periodLabel): string
    {
        $mobile = (int) ($devices['mobile'] ?? 0);
        $desktop = (int) ($devices['desktop'] ?? 0);
        $totalDevices = max(1, $mobile + $desktop + (int) ($devices['tablet'] ?? 0));
        $mobilePct = (int) round(($mobile / $totalDevices) * 100);
        $desktopPct = (int) round(($desktop / $totalDevices) * 100);

        $metrics = [
            ['Návštevy', (int) ($overview['visits'] ?? 0)],
            ['Zobrazenia', (int) ($overview['page_views'] ?? 0)],
            ['Unikátne', (int) ($overview['unique_visitors'] ?? 0)],
            ['Realtime', (int) ($overview['realtime_visitors'] ?? 0)],
        ];

        $metricHtml = '';
        foreach ($metrics as [$label, $value]) {
            $metricHtml .= sprintf(
                '<td style="width:25%%;padding:8px;text-align:center;">'
                . '<div style="font-size:26px;font-weight:700;color:#f8fafc;">%s</div>'
                . '<div style="font-size:11px;color:%s;margin-top:4px;">%s</div></td>',
                number_format($value),
                self::MUTED,
                $this->e($label)
            );
        }

        $content = sprintf(
            '<p style="margin:0 0 12px;color:%s;font-size:12px;">Obdobie: <strong style="color:%s;">%s</strong></p>'
            . '<table role="presentation" width="100%%" cellspacing="0" cellpadding="0"><tr>%s</tr></table>'
            . '<p style="margin:14px 0 6px;color:%s;font-size:12px;">Mobile / Desktop</p>'
            . '<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="background:#1e293b;border-radius:6px;">'
            . '<tr><td width="%d%%" style="background:#0369a1;height:8px;border-radius:6px 0 0 6px;"></td>'
            . '<td width="%d%%" style="background:#334155;height:8px;border-radius:0 6px 6px 0;"></td></tr></table>'
            . '<p style="margin:6px 0 0;color:%s;font-size:11px;">Mobile %d%% · Desktop %d%%</p>',
            self::TEXT,
            self::ACCENT,
            $this->e($periodLabel),
            $metricHtml,
            self::MUTED,
            max(1, $mobilePct),
            max(1, $desktopPct),
            self::MUTED,
            $mobilePct,
            $desktopPct
        );

        return $this->renderSection('Prehľad návštevnosti', 'chart', $content);
    }

    /**
     * @param list<array{ip: string, visits: int, top_uri: string}> $ipStats
     * @param list<string> $lines
     */
    private function renderIpStatsSection(array $ipStats, array &$lines): string
    {
        $lines[] = '--- Štatistiky IP ---';
        if ($ipStats === []) {
            $lines[] = 'Žiadne záznamy.';
            $lines[] = '';

            return $this->renderSection(
                'Štatistiky IP',
                'network',
                '<p style="margin:0;color:' . self::MUTED . ';">Žiadne záznamy za zvolené obdobie.</p>'
            );
        }

        $rows = '';
        foreach ($ipStats as $row) {
            $line = sprintf(
                'IP %s – návštev: %d – najčastejšie: %s',
                $row['ip'],
                $row['visits'],
                $row['top_uri']
            );
            $lines[] = $line;
            $rows .= sprintf(
                '<tr><td style="padding:6px 0;border-bottom:1px solid #1e293b;color:%s;font-family:Consolas,Monaco,monospace;font-size:12px;line-height:1.5;">'
                . '<span style="color:%s;">IP</span> %s '
                . '<span style="color:%s;">– návštev:</span> <span style="color:#f8fafc;">%d</span> '
                . '<span style="color:%s;">– najčastejšie:</span> <span style="color:%s;">%s</span>'
                . '</td></tr>',
                self::TEXT,
                self::ACCENT,
                $this->e($row['ip']),
                self::MUTED,
                $row['visits'],
                self::MUTED,
                self::GREEN,
                $this->e($row['top_uri'])
            );
        }
        $lines[] = '';

        return $this->renderSection(
            'Štatistiky IP',
            'network',
            '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">' . $rows . '</table>'
        );
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param list<string> $lines
     */
    private function renderListSection(
        string $title,
        string $icon,
        array $items,
        string $labelKey,
        string $valueKey,
        string $valueSuffix,
        array &$lines
    ): string {
        $lines[] = '--- ' . $title . ' ---';
        if ($items === []) {
            $lines[] = 'Žiadne záznamy.';
            $lines[] = '';

            return $this->renderSection(
                $title,
                $icon,
                '<p style="margin:0;color:' . self::MUTED . ';">Žiadne záznamy za zvolené obdobie.</p>'
            );
        }

        $rows = '';
        foreach ($items as $item) {
            $label = (string) ($item[$labelKey] ?? '-');
            $value = (int) ($item[$valueKey] ?? 0);
            $lines[] = sprintf('%s – %d %s', $label, $value, $valueSuffix);
            $rows .= sprintf(
                '<tr>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #1e293b;color:%s;font-family:Consolas,Monaco,monospace;font-size:12px;">%s</td>'
                . '<td align="right" style="padding:8px 10px;border-bottom:1px solid #1e293b;color:#f8fafc;font-weight:700;white-space:nowrap;">%d</td>'
                . '</tr>',
                self::TEXT,
                $this->e($label),
                $value
            );
        }
        $lines[] = '';

        return $this->renderSection(
            $title,
            $icon,
            '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">' . $rows . '</table>'
        );
    }

    /**
     * @param list<array{uri: string, views: int, title: string}> $articles
     * @param list<string> $lines
     */
    private function renderArticleSection(array $articles, array &$lines): string
    {
        $lines[] = '--- Top články ---';
        if ($articles === []) {
            $lines[] = 'Žiadne návštevy článkov.';
            $lines[] = '';

            return $this->renderSection(
                'Top články',
                'article',
                '<p style="margin:0;color:' . self::MUTED . ';">Žiadne návštevy článkov (/blog, /articles, …).</p>'
            );
        }

        $rows = '';
        foreach ($articles as $item) {
            $lines[] = sprintf('%s (%s) – %d zobrazení', $item['title'], $item['uri'], $item['views']);
            $rows .= sprintf(
                '<tr>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #1e293b;">'
                . '<div style="color:#f8fafc;font-size:13px;">%s</div>'
                . '<div style="color:%s;font-family:Consolas,Monaco,monospace;font-size:11px;margin-top:2px;">%s</div>'
                . '</td>'
                . '<td align="right" style="padding:8px 10px;border-bottom:1px solid #1e293b;color:%s;font-weight:700;white-space:nowrap;">%d</td>'
                . '</tr>',
                $this->e($item['title']),
                self::MUTED,
                $this->e($item['uri']),
                self::GREEN,
                $item['views']
            );
        }
        $lines[] = '';

        return $this->renderSection(
            'Top články',
            'article',
            '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">' . $rows . '</table>'
        );
    }

    /**
     * @param list<array{referer: string, visits: int}> $referers
     * @param list<string> $lines
     */
    private function renderRefererSection(array $referers, array &$lines): string
    {
        $lines[] = '--- Top odkazujúce stránky ---';
        if ($referers === []) {
            $lines[] = 'Žiadne odkazy.';
            $lines[] = '';

            return $this->renderSection(
                'Top odkazujúce stránky',
                'link',
                '<p style="margin:0;color:' . self::MUTED . ';">Žiadne referery za zvolené obdobie.</p>'
            );
        }

        $rows = '';
        foreach ($referers as $item) {
            $ref = (string) ($item['referer'] ?? 'direct');
            $lines[] = sprintf('%s – %d', $ref, (int) ($item['visits'] ?? 0));
            $rows .= sprintf(
                '<tr>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #1e293b;color:%s;font-size:12px;word-break:break-all;">%s</td>'
                . '<td align="right" style="padding:8px 10px;border-bottom:1px solid #1e293b;color:#f8fafc;font-weight:700;">%d</td>'
                . '</tr>',
                self::TEXT,
                $this->e($ref),
                (int) ($item['visits'] ?? 0)
            );
        }
        $lines[] = '';

        return $this->renderSection(
            'Top odkazujúce stránky',
            'link',
            '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">' . $rows . '</table>'
        );
    }

    /**
     * @param list<array<string, mixed>> $checks
     */
    private function renderHealthSection(string $overallStatus, array $checks): string
    {
        $rows = '';
        if ($checks === []) {
            $rows = '<tr><td colspan="3" style="padding:10px;color:' . self::MUTED . ';">Žiadne health checky.</td></tr>';
        } else {
            foreach ($checks as $check) {
                $name = (string) ($check['name'] ?? $check['check'] ?? 'check');
                $status = (string) ($check['status'] ?? 'unknown');
                $message = (string) ($check['message'] ?? '');
                $rows .= sprintf(
                    '<tr>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #1e293b;color:#f8fafc;">%s</td>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #1e293b;">%s</td>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #1e293b;color:%s;">%s</td>'
                    . '</tr>',
                    $this->e($name),
                    $this->statusBadge($status),
                    self::TEXT,
                    $this->e($message)
                );
            }
        }

        return $this->renderSection(
            'Systémové informácie',
            'server',
            '<p style="margin:0 0 12px;color:' . self::TEXT . ';">Celkový stav: ' . $this->statusBadge($overallStatus) . '</p>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">' . $rows . '</table>'
            . '<p style="margin:12px 0 0;color:' . self::MUTED . ';font-size:11px;">'
            . 'CPU/RAM/Disk/Docker procesy nie sú súčasťou CMS – zobrazujú sa dostupné health checky PaginiumCMS.'
            . '</p>'
        );
    }

    /**
     * @param array<string, int|string> $stats
     */
    private function renderFlatFileSection(array $stats): string
    {
        $rows = '';
        $items = [];
        foreach ($stats as $key => $value) {
            $items[] = ['key' => $key, 'value' => $value];
        }
        foreach (array_chunk($items, 3) as $chunk) {
            $cells = '';
            foreach ($chunk as $item) {
                $label = ucwords(str_replace('_', ' ', (string) $item['key']));
                $cells .= sprintf(
                    '<td style="width:33.33%%;padding:6px;vertical-align:top;">'
                    . '<div style="background:#1e293b;border:1px solid #334155;border-radius:8px;padding:12px;text-align:center;">'
                    . '<div style="font-size:22px;font-weight:700;color:#f8fafc;">%s</div>'
                    . '<div style="font-size:11px;color:%s;margin-top:4px;">%s</div>'
                    . '</div></td>',
                    $this->e((string) $item['value']),
                    self::MUTED,
                    $this->e($label)
                );
            }
            while (substr_count($cells, '<td') < 3) {
                $cells .= '<td style="width:33.33%;padding:6px;"></td>';
            }
            $rows .= '<tr>' . $cells . '</tr>';
        }

        return $this->renderSection(
            'PaginiumCMS – flat-file úložisko',
            'database',
            '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">' . $rows . '</table>'
        );
    }

    private function renderSection(string $title, string $icon, string $content): string
    {
        $emoji = match ($icon) {
            'chart' => '📊',
            'network' => '🌐',
            'globe' => '📄',
            'article' => '📝',
            'link' => '🔗',
            'server' => '🖥️',
            'database' => '🗂️',
            default => '📌',
        };

        return sprintf(
            '<div style="margin-bottom:16px;border:1px solid #1e293b;border-radius:10px;overflow:hidden;background:%s;">'
            . '<div style="padding:12px 16px;background:%s;border-bottom:1px solid #155e75;">'
            . '<span style="font-size:16px;margin-right:8px;">%s</span>'
            . '<span style="font-size:14px;font-weight:700;color:#e0f2fe;letter-spacing:0.02em;">%s</span>'
            . '</div>'
            . '<div style="padding:14px 16px;background:%s;">%s</div>'
            . '</div>',
            self::PANEL,
            self::HEADER,
            $emoji,
            $this->e($title),
            self::PANEL,
            $content
        );
    }

    private function wrapHtmlDocument(
        string $siteName,
        string $periodLabel,
        string $generatedAt,
        string $sectionsHtml
    ): string {
        return '<!DOCTYPE html>'
            . '<html lang="sk"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $this->e($siteName) . ' – Monitoring</title></head>'
            . '<body style="margin:0;padding:0;background:' . self::BG . ';font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:' . self::BG . ';padding:20px 10px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:720px;">'
            . '<tr><td style="background:linear-gradient(180deg,#0c4a6e 0%,#082f49 100%);border:1px solid #155e75;border-radius:12px 12px 0 0;padding:24px 20px;text-align:center;">'
            . '<div style="font-size:11px;color:#7dd3fc;text-transform:uppercase;letter-spacing:0.12em;">PaginiumCMS Monitoring</div>'
            . '<h1 style="margin:10px 0 6px;font-size:22px;font-weight:700;color:#f8fafc;">' . $this->e($siteName) . '</h1>'
            . '<p style="margin:0;font-size:13px;color:#bae6fd;">' . $this->e($periodLabel) . ' · ' . $this->e($generatedAt) . '</p>'
            . '</td></tr>'
            . '<tr><td style="background:' . self::PANEL . ';border:1px solid #1e293b;border-top:none;border-radius:0 0 12px 12px;padding:18px 16px;">'
            . $sectionsHtml
            . '<p style="margin:8px 0 0;font-size:11px;color:' . self::MUTED . ';text-align:center;">'
            . 'Vygenerované: ' . $this->e($generatedAt) . ' · PaginiumCMS'
            . '</p>'
            . '</td></tr>'
            . '</table></td></tr></table>'
            . '</body></html>';
    }

    private function statusBadge(string $status): string
    {
        $normalized = strtolower($status);
        [$bg, $fg, $label] = match ($normalized) {
            HealthStatus::STATUS_PASS, 'ok', 'healthy', 'pass' => ['#14532d', '#86efac', 'OK'],
            HealthStatus::STATUS_WARN, 'warning', 'warn' => ['#713f12', '#fde047', 'Warning'],
            HealthStatus::STATUS_FAIL, 'error', 'critical', 'fail' => ['#7f1d1d', '#fca5a5', 'Fail'],
            HealthStatus::STATUS_SKIP, 'skip' => ['#334155', '#cbd5e1', 'Skip'],
            default => ['#1e3a5f', '#7dd3fc', ucfirst($normalized)],
        };

        return sprintf(
            '<span style="display:inline-block;padding:2px 8px;border-radius:4px;background:%s;color:%s;font-size:10px;font-weight:700;text-transform:uppercase;">%s</span>',
            $bg,
            $fg,
            $this->e($label)
        );
    }

    private function analyticsPeriod(string $interval): string
    {
        return match ($interval) {
            'week' => 'week',
            default => 'today',
        };
    }

    private function intervalLabel(string $interval): string
    {
        return match ($interval) {
            'hour' => 'Posledná hodina / dnes',
            'week' => 'Posledný týždeň',
            default => 'Dnes',
        };
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
