<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PaginiumCMS\Support\JsonHelper;

class TrashController
{
    public function __construct(
        private TrashService $trashService,
        private ContentIndexService $contentIndex,
        private ContentRepositoryInterface $contentRepository
    ) {
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'success' => true,
            'data' => $this->trashService->listItems(),
        ]);
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function restore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        if ($id === '') {
            return $this->json($response, ['success' => false, 'error' => 'Chýba ID položky'], 400);
        }

        try {
            $originalPath = $this->trashService->restore($id);
            $this->contentIndex->rebuild($this->contentRepository);
        } catch (FlatFileException $e) {
            return $this->json($response, ['success' => false, 'error' => $e->getMessage()], 404);
        }

        return $this->json($response, [
            'success' => true,
            'message' => 'Položka bola obnovená',
            'data' => ['originalPath' => $originalPath],
        ]);
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
