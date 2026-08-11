<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Seo\Services\NotFoundHitStore;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin 404 hit report (It.80b).
 */
final class NotFoundReportController
{
    public function __construct(
        private NotFoundHitStore $store,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $days = max(1, min(90, (int) ($request->getQueryParams()['days'] ?? 7)));
        $limit = max(1, min(200, (int) ($request->getQueryParams()['limit'] ?? 50)));

        return $this->json->success($response, [
            'days' => $days,
            'paths' => $this->store->topPaths($days, $limit),
        ]);
    }

    public function export(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $days = max(1, min(90, (int) ($request->getQueryParams()['days'] ?? 7)));
        $csv = $this->store->exportCsv($days);
        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="not_found_' . date('Y-m-d') . '.csv"');
    }
}
