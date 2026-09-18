<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Logging\Services\ApplicationLogExportService;
use PaginiumCMS\Core\Logging\Services\ApplicationLogMessageFormatter;
use PaginiumCMS\Core\Logging\Services\ApplicationLogReader;
use PaginiumCMS\Core\Logging\Services\LogRetentionService;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LogController
{
    public function __construct(
        private ApplicationLogReader $logReader,
        private ApplicationLogMessageFormatter $logFormatter,
        private ApplicationLogExportService $logExport,
        private LogRetentionService $logRetention,
        private JsonResponder $json
    ) {
    }

    public function stats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $hours = max(1, min(168, (int) ($params['hours'] ?? 24)));

        return $this->json->success($response, [
            'hours' => $hours,
            'by_severity' => $this->logReader->severityStats($hours),
            'sources' => $this->logReader->availableSources(),
            'severities' => [
                LogSeverity::DEBUG,
                LogSeverity::INFO,
                LogSeverity::WARNING,
                LogSeverity::ERROR,
                LogSeverity::CRITICAL,
            ],
        ]);
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $limit = max(1, min(500, (int) ($params['limit'] ?? 100)));
        $offset = max(0, (int) ($params['offset'] ?? 0));
        $severity = isset($params['severity']) ? (string) $params['severity'] : null;
        $source = isset($params['source']) ? (string) $params['source'] : null;
        $category = isset($params['category']) ? (string) $params['category'] : null;
        $search = isset($params['search']) ? (string) $params['search'] : null;
        $archivedFilter = $this->resolveArchivedFilter((string) ($params['archived'] ?? 'active'));

        if ($severity !== null && $severity !== '') {
            $severity = strtoupper($severity);
            if (!LogSeverity::isValid($severity)) {
                return $this->json->error($response, 'Neplatná severity', 400);
            }
        }

        $total = $this->logReader->count($severity, $source, $category, $search, $archivedFilter);
        $items = array_map(
            fn (array $item): array => $this->logFormatter->enrich($item),
            $this->logReader->query($severity, $source, $category, $search, $limit, $offset, $archivedFilter)
        );

        return $this->json->success($response, [
            'items' => $items,
            'limit' => $limit,
            'offset' => $offset,
            'total' => $total,
            'sources' => $this->logReader->availableSources(),
        ]);
    }

    public function purge(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $removed = $this->logRetention->purgeOldLogs();

        return $this->json->success(
            $response,
            ['removed_files' => $removed],
            200,
            'Staré logy boli vyčistené podľa retentionDays'
        );
    }

    public function bulkAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, 'Neplatné dáta požiadavky', 400);
        }

        $ids = $this->normalizeIds($data['ids'] ?? null);
        $action = (string) ($data['action'] ?? '');

        if ($ids === []) {
            return $this->json->error($response, 'Vyberte aspoň jeden log', 400);
        }

        if (!in_array($action, ['delete', 'archive'], true)) {
            return $this->json->error($response, 'Neplatná bulk akcia', 422);
        }

        $batch = $action === 'delete'
            ? $this->logReader->deleteByIds($ids)
            : $this->logReader->archiveByIds($ids);

        return $this->json->success(
            $response,
            $batch->toArray(),
            200,
            $action === 'delete' ? 'Vybrané logy boli vymazané' : 'Vybrané logy boli archivované'
        );
    }

    public function export(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $format = strtolower((string) ($params['format'] ?? 'txt'));
        if (!in_array($format, ['txt', 'pdf', 'zip'], true)) {
            return $this->json->error($response, 'Neplatný formát exportu', 422);
        }

        $ids = isset($params['ids']) ? $this->normalizeIds(explode(',', (string) $params['ids'])) : [];

        $filters = [
            'severity' => isset($params['severity']) ? (string) $params['severity'] : null,
            'source' => isset($params['source']) ? (string) $params['source'] : null,
            'category' => isset($params['category']) ? (string) $params['category'] : null,
            'search' => isset($params['search']) ? (string) $params['search'] : null,
            'archived' => $this->resolveArchivedFilter((string) ($params['archived'] ?? 'active')),
        ];

        return $this->exportDownload($response, $format, $ids !== [] ? $ids : null, $filters);
    }

    public function exportPost(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, 'Neplatné dáta požiadavky', 400);
        }

        $format = strtolower((string) ($data['format'] ?? 'txt'));
        if (!in_array($format, ['txt', 'pdf', 'zip'], true)) {
            return $this->json->error($response, 'Neplatný formát exportu', 422);
        }

        $ids = $this->normalizeIds($data['ids'] ?? null);
        $filters = is_array($data['filters'] ?? null) ? $data['filters'] : null;

        return $this->exportDownload($response, $format, $ids !== [] ? $ids : null, $filters);
    }

    /**
     * @param list<string>|null $ids
     * @param array<string, mixed>|null $filters
     */
    private function exportDownload(
        ResponseInterface $response,
        string $format,
        ?array $ids,
        ?array $filters
    ): ResponseInterface {
        $severity = isset($filters['severity']) ? (string) $filters['severity'] : null;
        $source = isset($filters['source']) ? (string) $filters['source'] : null;
        $category = isset($filters['category']) ? (string) $filters['category'] : null;
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $archived = $this->resolveArchivedFilter((string) ($filters['archived'] ?? 'active'));

        if ($severity !== null && $severity !== '') {
            $severity = strtoupper($severity);
            if (!LogSeverity::isValid($severity)) {
                return $this->json->error($response, 'Neplatná severity', 400);
            }
        }

        $raw = $this->logExport->resolveEntries($ids, $severity, $source, $category, $search, $archived);
        if ($raw === []) {
            return $this->json->error($response, 'Žiadne logy pre export', 404);
        }

        $records = $this->logExport->extendedRecords($raw);
        $stamp = date('Y-m-d_His');

        return match ($format) {
            'pdf' => $this->binaryResponse(
                $response,
                $this->logExport->toPdfBinary($records),
                'application/pdf',
                "paginium-logs-$stamp.pdf"
            ),
            'zip' => $this->binaryResponse(
                $response,
                $this->logExport->toZipBinary($records),
                'application/zip',
                "paginium-logs-$stamp.zip"
            ),
            default => $this->binaryResponse(
                $response,
                $this->logExport->toPlainText($records),
                'text/plain; charset=utf-8',
                "paginium-logs-$stamp.txt"
            ),
        };
    }

    private function binaryResponse(
        ResponseInterface $response,
        string $body,
        string $contentType,
        string $filename
    ): ResponseInterface {
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) ?? 'export.dat';

        $response->getBody()->write($body);

        return $response
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Disposition', 'attachment; filename="' . $safeName . '"')
            ->withHeader('Cache-Control', 'no-store')
            ->withStatus(200);
    }

    public function deleteAll(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $result = $this->logReader->deleteAll();

        return $this->json->success(
            $response,
            $result,
            200,
            'Všetky logy boli vymazané'
        );
    }

    private function resolveArchivedFilter(string $value): string
    {
        return match ($value) {
            'archived', '1', 'true' => 'archived',
            'all' => 'all',
            default => 'active',
        };
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function normalizeIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($id) => trim((string) $id), $value),
            static fn (string $id) => $id !== ''
        ));
    }
}
