<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SecurityAuditController
{
    public function __construct(
        private SecurityAuditStore $store,
        private JsonResponder $json
    ) {
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $filters = array_filter([
            'type' => (string) ($params['type'] ?? ''),
            'severity' => (string) ($params['severity'] ?? ''),
            'user_id' => (string) ($params['user_id'] ?? ''),
        ], static fn (string $value): bool => $value !== '');

        $limit = max(1, min(500, (int) ($params['limit'] ?? 100)));
        $events = $this->store->list($filters, $limit);

        return $this->json->success($response, [
            'total' => count($events),
            'events' => $events,
        ]);
    }

    public function export(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $filters = array_filter([
            'type' => (string) ($params['type'] ?? ''),
            'severity' => (string) ($params['severity'] ?? ''),
            'user_id' => (string) ($params['user_id'] ?? ''),
        ], static fn (string $value): bool => $value !== '');

        $csv = $this->store->exportCsv($filters, 1000);
        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="security_audit_' . date('Y-m-d') . '.csv"');
    }
}
