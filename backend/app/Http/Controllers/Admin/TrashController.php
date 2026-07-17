<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TrashController
{
    public function __construct(
        private TrashService $trashService,
        private ContentIndexService $contentIndex,
        private ContentRepositoryInterface $contentRepository,
        private JsonResponder $json
    ) {
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->trashService->listItems());
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function restore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        if ($id === '') {
            return $this->json->error($response, 'Chýba ID položky', 400);
        }

        try {
            $originalPath = $this->trashService->restore($id);
            $this->contentIndex->rebuild($this->contentRepository);
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 404);
        }

        return $this->json->success(
            $response,
            ['originalPath' => $originalPath],
            200,
            'Položka bola obnovená'
        );
    }
}
