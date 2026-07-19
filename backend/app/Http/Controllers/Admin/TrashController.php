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
use Slim\Psr7\Stream;

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
        $ids = $this->extractIds($request);
        if ($ids === null) {
            return $this->json->error($response, 'Vyžaduje sa pole ids', 400);
        }

        $batch = new BulkBatchResult();
        $restoredAny = false;

        foreach ($ids as $id) {
            try {
                $this->trashService->restore($id);
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

    public function bulkPurge(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ids = $this->extractIds($request);
        if ($ids === null) {
            return $this->json->error($response, 'Vyžaduje sa pole ids', 400);
        }

        $batch = new BulkBatchResult();

        foreach ($ids as $id) {
            try {
                $this->trashService->purge($id);
                $batch->addSuccess($id);
            } catch (FlatFileException $e) {
                $batch->addFailure($id, $e->getMessage());
            }
        }

        return $this->json->success(
            $response,
            $batch->toArray(),
            200,
            'Trvalé zmazanie dokončené'
        );
    }

    public function emptyTrash(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $removed = $this->trashService->purgeAll();

        return $this->json->success(
            $response,
            ['removed' => $removed],
            200,
            $removed > 0 ? 'Kôš bol vyprázdnený' : 'Kôš je už prázdny'
        );
    }

    public function bulkBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ids = $this->extractIds($request);
        if ($ids === null) {
            return $this->json->error($response, 'Vyžaduje sa pole ids', 400);
        }

        try {
            $archive = $this->trashService->backupItems($ids);
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        }

        return $this->json->success(
            $response,
            [
                'filename' => $archive['filename'],
                'size' => $archive['size'],
                'count' => $archive['count'],
                'downloadUrl' => '/api/admin/trash/backups/' . rawurlencode($archive['filename']) . '/download',
            ],
            201,
            'Záloha koša bola vytvorená'
        );
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function downloadBackup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $filename = (string) ($args['filename'] ?? '');
        $path = $this->trashService->resolveBackupPath($filename);
        if ($path === null) {
            return $this->json->error($response, 'Záloha neexistuje', 404);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return $this->json->error($response, 'Nepodarilo sa otvoriť zálohu', 500);
        }

        $stream = new Stream($handle);
        $response = $response
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', 'attachment; filename="' . basename($path) . '"')
            ->withHeader('Content-Length', (string) filesize($path))
            ->withBody($stream);

        return $response->withStatus(200);
    }

    /**
     * @return list<string>|null
     */
    private function extractIds(ServerRequestInterface $request): ?array
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data) || !isset($data['ids']) || !is_array($data['ids'])) {
            return null;
        }

        $ids = array_values(array_filter(
            array_map(static fn ($id): string => is_string($id) ? trim($id) : '', $data['ids']),
            static fn (string $id): bool => $id !== ''
        ));

        if ($ids === []) {
            return null;
        }

        return $ids;
    }
}
