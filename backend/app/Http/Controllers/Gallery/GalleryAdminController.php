<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Gallery;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Gallery\Contracts\GalleryRepositoryInterface;
use PaginiumCMS\Modules\Gallery\Services\GalleryItemValidator;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GalleryAdminController
{
    public function __construct(
        private GalleryRepositoryInterface $repository,
        private GalleryItemValidator $validator,
        private JsonResponder $json
    ) {
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $items = array_map(
            static fn ($item) => $item->jsonSerialize(),
            $this->repository->findAllOrdered()
        );

        return $this->json->success($response, [
            'items' => $items,
            'count' => count($items),
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->decodeBody($request);
        if ($payload === null) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'gallery'), 400);
        }

        $error = $this->validator->validate($payload, true);
        if ($error !== null) {
            return $this->json->error($response, $error, 422);
        }

        try {
            $item = $this->repository->create($payload);

            return $this->json->success(
                $response,
                $item->jsonSerialize(),
                201,
                Lang::get('created', [], 'gallery')
            );
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        if ($id === '') {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'gallery'), 400);
        }

        $payload = $this->decodeBody($request);
        if ($payload === null) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'gallery'), 400);
        }

        $error = $this->validator->validate($payload, false);
        if ($error !== null) {
            return $this->json->error($response, $error, 422);
        }

        try {
            $item = $this->repository->update($id, $payload);

            return $this->json->success(
                $response,
                $item->jsonSerialize(),
                200,
                Lang::get('updated', [], 'gallery')
            );
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 404);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        if ($id === '') {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'gallery'), 400);
        }

        try {
            $this->repository->delete($id);

            return $this->json->success(
                $response,
                ['deleted' => true],
                200,
                Lang::get('deleted', [], 'gallery')
            );
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 404);
        }
    }

    public function reorder(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->decodeBody($request);
        if ($payload === null || !isset($payload['ids']) || !is_array($payload['ids'])) {
            return $this->json->error($response, Lang::get('invalid_reorder', [], 'gallery'), 400);
        }

        /** @var list<string> $ids */
        $ids = array_values(array_filter(
            $payload['ids'],
            static fn ($id): bool => is_string($id) && $id !== ''
        ));

        try {
            $this->repository->reorder($ids);

            return $this->json->success(
                $response,
                ['ids' => $ids],
                200,
                Lang::get('reordered', [], 'gallery')
            );
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        }
    }

    public function export(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $items = array_map(
            static fn ($item) => $item->jsonSerialize(),
            $this->repository->findAllOrdered()
        );

        $payload = [
            'version' => 1,
            'exportedAt' => date('c'),
            'items' => $items,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return $this->json->error($response, Lang::get('export_failed', [], 'gallery'), 500);
        }

        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader(
                'Content-Disposition',
                'attachment; filename="gallery-export-' . date('Y-m-d') . '.json"'
            );
    }

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->decodeBody($request);
        if ($payload === null) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'gallery'), 400);
        }

        $rawItems = $payload['items'] ?? null;
        if (!is_array($rawItems)) {
            return $this->json->error($response, Lang::get('invalid_import', [], 'gallery'), 400);
        }

        /** @var list<array<string, mixed>> $items */
        $items = [];
        foreach ($rawItems as $entry) {
            if (!is_array($entry)) {
                return $this->json->error($response, Lang::get('invalid_import', [], 'gallery'), 400);
            }
            $error = $this->validator->validate($entry, true);
            if ($error !== null) {
                return $this->json->error($response, $error, 422);
            }
            $items[] = $entry;
        }

        $replace = !array_key_exists('replace', $payload) || (bool) $payload['replace'];

        try {
            $result = $this->repository->importItems($items, $replace);

            return $this->json->success(
                $response,
                $result,
                200,
                Lang::get('imported', [], 'gallery')
            );
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeBody(ServerRequestInterface $request): ?array
    {
        $data = RequestJsonBody::decode($request);

        return is_array($data) ? $data : null;
    }
}
