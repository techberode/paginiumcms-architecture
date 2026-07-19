<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Logging\Services\AccessLogService;
use PaginiumCMS\Core\Logging\Services\ApplicationLogReader;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LogController
{
    public function __construct(
        private ApplicationLogReader $logReader,
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

        if ($severity !== null && $severity !== '' && !LogSeverity::isValid($severity)) {
            return $this->json->error($response, 'Neplatná severity', 400);
        }

        $items = $this->logReader->query($severity, $source, $category, $search, $limit, $offset);

        return $this->json->success($response, [
            'items' => $items,
            'limit' => $limit,
            'offset' => $offset,
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
}
