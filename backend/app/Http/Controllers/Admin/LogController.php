<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Logging\Services\AccessLogService;
use PaginiumCMS\Core\Logging\Services\ApplicationLogMessageFormatter;
use PaginiumCMS\Core\Logging\Services\ApplicationLogReader;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LogController
{
    public function __construct(
        private ApplicationLogReader $logReader,
        private ApplicationLogMessageFormatter $logFormatter,
        private AccessLogService $accessLog,
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
        $removed = $this->accessLog->purgeOldLogs();

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
