<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Content;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\Search\Services\AdvancedSearchService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Fulltext search nad content indexom + admin command palette (It.43).
 */
final class SearchController
{
    public function __construct(
        private ContentIndexService $index,
        private ContentRepositoryInterface $repository,
        private AdvancedSearchService $advancedSearch,
        private AuthenticationInterface $auth,
        private JsonResponder $json
    ) {
    }

    public function search(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->index->ensureBuilt($this->repository);
        $params = $request->getQueryParams();
        $q = trim((string) ($params['q'] ?? $params['search'] ?? ''));
        $scope = strtolower(trim((string) ($params['scope'] ?? 'public')));
        $limitPerType = (int) ($params['limit'] ?? 8);
        $types = $this->parseTypes((string) ($params['types'] ?? ''));
        $legacyType = isset($params['type']) ? (string) $params['type'] : null;

        if ($legacyType !== null && $legacyType !== '' && $types === []) {
            if ($legacyType !== 'page' && $legacyType !== 'article') {
                return $this->json->error($response, Lang::get('invalid_type', [], 'content'), 400);
            }
            $types = [$legacyType];
        }

        if (mb_strlen($q) < 2) {
            return $scope === 'admin'
                ? $this->json->success($response, [
                    'query' => $q,
                    'scope' => 'admin',
                    'results' => [],
                    'counts' => ['page' => 0, 'article' => 0, 'media' => 0, 'route' => 0],
                ])
                : $this->json->success($response, []);
        }

        if ($scope === 'admin') {
            $user = $this->resolveUser($request);
            if (!$user instanceof User) {
                return $this->json->error($response, Lang::get('unauthorized', [], 'auth'), 401);
            }

            return $this->json->success(
                $response,
                $this->advancedSearch->searchAdmin($q, $types, $limitPerType, $user)
            );
        }

        if ($scope !== 'public') {
            return $this->json->error($response, 'Invalid scope. Use public or admin.', 400);
        }

        $results = $this->advancedSearch->searchPublic($q, $types, $limitPerType);

        return $this->json->success($response, $results);
    }

    /**
     * @return list<string>
     */
    private function parseTypes(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $raw));
        $types = [];
        foreach ($parts as $part) {
            if ($part !== '') {
                $types[] = strtolower($part);
            }
        }

        return $types;
    }

    private function resolveUser(ServerRequestInterface $request): ?User
    {
        $user = $request->getAttribute('user');
        if ($user instanceof User) {
            return $user;
        }

        return $this->auth->isAuthenticated() ? $this->auth->getCurrentUser() : null;
    }
}
