<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\PublicApi;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Services\PublishedStaffDirectory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Opt-in staff cards for the public Contact page (It.93o).
 */
final class StaffDirectoryController
{
    public function __construct(
        private PublishedStaffDirectory $directory,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->directory->publicLists());
    }
}
