<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Gallery;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Gallery\Contracts\GalleryRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GalleryPublicController
{
    public function __construct(
        private GalleryRepositoryInterface $repository,
        private JsonResponder $json
    ) {
    }

    public function listPublished(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $items = array_map(
            static fn ($item) => $item->jsonSerialize(),
            $this->repository->findPublishedOrdered()
        );

        return $this->json->success($response, [
            'items' => $items,
            'count' => count($items),
        ]);
    }
}
