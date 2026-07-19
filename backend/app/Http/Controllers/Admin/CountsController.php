<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Admin\Services\AdminCountsService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/admin/counts — sidebar / list KPI counts (Iteration 42).
 */
final class CountsController
{
    public function __construct(
        private AdminCountsService $counts,
        private JsonResponder $json
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $viewer = $request->getAttribute('user');
        $user = $viewer instanceof User ? $viewer : null;

        return $this->json->success($response, $this->counts->collect($user));
    }
}
