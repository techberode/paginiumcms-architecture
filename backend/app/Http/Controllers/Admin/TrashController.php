<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PaginiumCMS\Http\Support\BulkBatchResult;
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

    public function bulkRestore(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data) || !isset($data['ids']) || !is_array($data['ids'])) {
            return $this->json->error($response, 'Vyžaduje sa pole ids', 400);
        }

        $ids = array_values(array_filter(
            array_map(static fn ($id): string => is_string($id) ? trim($id) : '', $data['ids']),
            static fn (string $id): bool => $id !== ''
        ));

        if ($ids === []) {
            return $this->json->error($response, 'Vyžaduje sa aspoň jedno ID', 400);
        }

        $batch = new BulkBatchResult();
        $restoredAny = false;

        foreach ($ids as $id) {
            try {
                $originalPath = $this->trashService->restore($id);
                $batch->addSuccess($id);
                $restoredAny = true;
            } catch (FlatFileException $e) {
                $batch->addFailure($id, $e->getMessage());
            }
        }

        if ($restoredAny) {
            $this->contentIndex->rebuild($this->contentRepository);
        }

        return $this->json->success(
            $response,
            $batch->toArray(),
            200,
            'Hromadná obnova dokončená'
        );
    }
}
