<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\Conflict\Models\ConflictRecord;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * === Controller: ConflictController (Admin) ===
 * Prehľad zachytených konfliktov obsahu (Iterácia 3).
 *
 *  - GET    /api/admin/conflicts       : zoznam najnovších konfliktov
 *  - DELETE /api/admin/conflicts       : vyčistenie logu
 */
final class ConflictController
{
    public function __construct(private ConflictLoggerInterface $conflicts)
    {
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int) $params['limit'] : 100;

        $records = array_map(
            static fn (ConflictRecord $r): array => $r->jsonSerialize(),
            $this->conflicts->getRecent($limit)
        );

        return $this->json($response, ['success' => true, 'data' => ['conflicts' => $records]]);
    }

    public function clear(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->conflicts->clear();

        return $this->json($response, ['success' => true, 'message' => 'Log konfliktov vyčistený']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
