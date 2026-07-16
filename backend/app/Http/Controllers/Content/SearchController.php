<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Content;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Fulltext search nad content indexom (Iterácia 19).
 */
final class SearchController
{
    public function __construct(
        private ContentIndexService $index,
        private ContentRepositoryInterface $repository,
        private JsonResponder $json
    ) {
    }

    public function search(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->index->ensureBuilt($this->repository);
        $params = $request->getQueryParams();
        $q = trim((string) ($params['q'] ?? $params['search'] ?? ''));
        $type = isset($params['type']) ? (string) $params['type'] : null;
        $limit = min(100, max(1, (int) ($params['limit'] ?? 20)));

        if ($type !== null && $type !== 'page' && $type !== 'article') {
            return $this->json->error($response, Lang::get('invalid_type', [], 'content'), 400);
        }

        if (mb_strlen($q) < 2) {
            return $this->json->success($response, []);
        }

        $results = array_map(
            static fn ($entry) => $entry->toSearchResult(),
            $this->index->search($q, $type, $limit)
        );

        return $this->json->success($response, $results);
    }
}
