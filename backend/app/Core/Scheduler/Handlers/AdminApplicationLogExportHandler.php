<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Logging\Services\AdminExportArtifactStore;
use PaginiumCMS\Core\Logging\Services\ApplicationLogExportService;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

final class AdminApplicationLogExportHandler implements JobHandlerInterface
{
    public function __construct(
        private ApplicationLogExportService $logExport,
        private AdminExportArtifactStore $artifacts,
    ) {
    }

    public function key(): string
    {
        return 'admin_logs.export';
    }

    public function label(): string
    {
        return 'Async application log export (It.100e)';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload = []): JobRunResult
    {
        $artifactId = (string) ($payload['artifact_id'] ?? '');
        $format = strtolower((string) ($payload['format'] ?? 'txt'));
        if ($artifactId === '' || !in_array($format, ['txt', 'pdf', 'zip'], true)) {
            return new JobRunResult(false, 'Invalid export payload');
        }

        /** @var list<string>|null $ids */
        $ids = isset($payload['ids']) && is_array($payload['ids']) ? array_values(array_map('strval', $payload['ids'])) : null;
        $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : null;

        $severity = isset($filters['severity']) ? (string) $filters['severity'] : null;
        $source = isset($filters['source']) ? (string) $filters['source'] : null;
        $category = isset($filters['category']) ? (string) $filters['category'] : null;
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $archived = $this->archivedFilter((string) ($filters['archived'] ?? 'active'));

        try {
            $raw = $this->logExport->resolveEntries($ids, $severity, $source, $category, $search, $archived);
            if ($raw === []) {
                $this->artifacts->markFailed($artifactId, 'No logs matched export filters');

                return new JobRunResult(false, 'No logs to export');
            }

            $records = $this->logExport->extendedRecords($raw);
            $stamp = date('Y-m-d_His');

            $binary = match ($format) {
                'pdf' => $this->logExport->toPdfBinary($records),
                'zip' => $this->logExport->toZipBinary($records),
                default => $this->logExport->toPlainText($records),
            };

            $contentType = match ($format) {
                'pdf' => 'application/pdf',
                'zip' => 'application/zip',
                default => 'text/plain; charset=utf-8',
            };
            $filename = match ($format) {
                'pdf' => "paginium-logs-$stamp.pdf",
                'zip' => "paginium-logs-$stamp.zip",
                default => "paginium-logs-$stamp.txt",
            };

            $this->artifacts->markReady($artifactId, $binary, $contentType, $filename, $filters);

            return new JobRunResult(true, 'Export ready', ['artifact_id' => $artifactId]);
        } catch (\Throwable $e) {
            $this->artifacts->markFailed($artifactId, $e->getMessage());

            return new JobRunResult(false, $e->getMessage());
        }
    }

    private function archivedFilter(string $value): string
    {
        return match ($value) {
            'archived', '1', 'true' => 'archived',
            'all' => 'all',
            default => 'active',
        };
    }
}
