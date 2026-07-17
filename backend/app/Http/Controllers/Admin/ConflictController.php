<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\Conflict\Models\ConflictRecord;
use PaginiumCMS\Http\Support\JsonResponder;
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
    public function __construct(
        private ConflictLoggerInterface $conflicts,
        private JsonResponder $json
    ) {
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int) $params['limit'] : 100;

        $records = array_map(
            static fn (ConflictRecord $r): array => $r->jsonSerialize(),
            $this->conflicts->getRecent($limit)
        );

        return $this->json->success($response, ['conflicts' => $records]);
    }

    public function clear(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->conflicts->clear();

        return $this->json->success($response, null, 200, 'Log konfliktov vyčistený');
    }
}
