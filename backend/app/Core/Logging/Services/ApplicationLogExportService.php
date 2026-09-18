<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\SimpleTextPdf;
use ZipArchive;

/**
 * Extended log export (full record payload, not UI-truncated view).
 */
final class ApplicationLogExportService
{
    public const MAX_EXPORT_ENTRIES = 5000;

    public function __construct(
        private ApplicationLogReader $reader,
        private ApplicationLogMessageFormatter $formatter
    ) {
    }

    /**
     * @param list<string>|null $ids
     * @return list<array<string, mixed>>
     */
    public function resolveEntries(
        ?array $ids,
        ?string $severity,
        ?string $source,
        ?string $category,
        ?string $search,
        string $archivedFilter,
        int $maxEntries = self::MAX_EXPORT_ENTRIES
    ): array {
        $max = max(1, min(self::MAX_EXPORT_ENTRIES, $maxEntries));

        if ($ids !== null && $ids !== []) {
            return array_slice($this->reader->findByIds($ids), 0, $max);
        }

        return $this->reader->collectForExport($severity, $source, $category, $search, $archivedFilter, $max);
    }

    /**
     * @param list<array<string, mixed>> $rawEntries
     * @return list<array<string, mixed>>
     */
    public function extendedRecords(array $rawEntries): array
    {
        $out = [];
        foreach ($rawEntries as $entry) {
            $enriched = $this->formatter->enrich($entry);
            $out[] = [
                'id' => (string) ($enriched['id'] ?? ''),
                'timestamp' => (string) ($enriched['timestamp'] ?? ''),
                'severity' => (string) ($enriched['severity'] ?? ''),
                'source' => (string) ($enriched['source'] ?? ''),
                'category' => (string) ($enriched['category'] ?? ''),
                'message' => (string) ($enriched['message'] ?? ''),
                'display_message' => (string) ($enriched['display_message'] ?? ''),
                'userId' => $enriched['userId'] ?? null,
                'ip' => $enriched['ip'] ?? null,
                'file' => $enriched['file'] ?? null,
                'line' => $enriched['line'] ?? null,
                'archived' => (bool) ($enriched['archived'] ?? false),
                'archivedAt' => $enriched['archivedAt'] ?? null,
                'context' => $enriched['context'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public function toPlainText(array $records): string
    {
        $blocks = [];
        foreach ($records as $record) {
            $blocks[] = $this->formatRecordBlock($record);
            $blocks[] = str_repeat('-', 72);
        }

        return implode("\n", $blocks) . "\n";
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public function toPdfBinary(array $records): string
    {
        $lines = ['PaginiumCMS — application log export', 'Generated: ' . date('c'), ''];
        foreach ($records as $record) {
            foreach (explode("\n", $this->formatRecordBlock($record)) as $line) {
                $lines[] = $line;
            }
            $lines[] = '';
        }

        return SimpleTextPdf::fromLines($lines, 'PaginiumCMS Log Export');
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public function toZipBinary(array $records): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pg_log_zip_');
        if ($tmp === false) {
            throw new \RuntimeException('Cannot create temp file for log export');
        }

        $zipPath = $tmp . '.zip';
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot open ZIP archive for log export');
        }

        $zip->addFromString('logs-extended.txt', $this->toPlainText($records));
        $zip->addFromString(
            'logs-extended.json',
            JsonHelper::encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        foreach ($records as $record) {
            $id = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($record['id'] ?? 'entry')) ?: 'entry';
            $zip->addFromString(
                'entries/' . $id . '.json',
                JsonHelper::encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        $zip->close();

        $binary = file_get_contents($zipPath);
        @unlink($zipPath);

        if ($binary === false) {
            throw new \RuntimeException('Cannot read log export ZIP');
        }

        return $binary;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function formatRecordBlock(array $record): string
    {
        $contextJson = JsonHelper::encode(
            $record['context'] ?? null,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return implode("\n", [
            'ID: ' . ($record['id'] ?? ''),
            'Timestamp: ' . ($record['timestamp'] ?? ''),
            'Severity: ' . ($record['severity'] ?? ''),
            'Source: ' . ($record['source'] ?? ''),
            'Category: ' . ($record['category'] ?? ''),
            'User ID: ' . (string) ($record['userId'] ?? ''),
            'IP: ' . (string) ($record['ip'] ?? ''),
            'File: ' . (string) ($record['file'] ?? '') . (isset($record['line']) ? ':' . (string) $record['line'] : ''),
            'Archived: ' . (($record['archived'] ?? false) ? 'yes' : 'no'),
            'Message: ' . ($record['message'] ?? ''),
            'Display: ' . ($record['display_message'] ?? ''),
            'Context JSON:',
            $contextJson,
        ]);
    }
}
