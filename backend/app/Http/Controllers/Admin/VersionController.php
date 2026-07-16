<?php
// backend/app/Http/Controllers/Admin/VersionController.php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Versioning\Services\ContentVersioningService;
use PaginiumCMS\Core\Versioning\Services\EnhancedVersionManager;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PaginiumCMS\Support\JsonHelper;

class VersionController
{
    public function __construct(
        private EnhancedVersionManager $versionManager,
        private ContentVersioningService $contentVersioning
    ) {
    }

    /**
     * GET /api/admin/versions/{contentId}
     * Získa históriu verzií pre obsah
 * @param array<int|string, mixed> $args
 */public function getHistory(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $contentId = $args['contentId'] ?? '';

        try {
            $history = $this->versionManager->getVersionHistory($contentId);

            $response->getBody()->write(JsonHelper::encode([
                'success' => true,
                'data' => [
                    'content_id' => $contentId,
                    'versions' => $history,
                    'total' => count($history)
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /api/admin/versions/{contentId}/{version}
     * Získa konkrétnu verziu
 * @param array<int|string, mixed> $args
 */public function getVersion(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $contentId = $args['contentId'] ?? '';
        $version = (int)($args['version'] ?? 0);

        try {
            $versionData = $this->versionManager->getVersion($contentId, $version);

            if (!$versionData) {
                $response->getBody()->write(JsonHelper::encode([
                    'success' => false,
                    'error' => 'Version not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(JsonHelper::encode([
                'success' => true,
                'data' => $versionData->toArray()
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * POST /api/admin/versions/restore
     * Obnoví verziu do live flat-file obsahu
     */
    public function restoreVersion(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string)$request->getBody(), true);
        $contentId = $data['content_id'] ?? '';
        $version = (int)($data['version'] ?? 0);

        if (empty($contentId) || $version <= 0) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => 'Content ID and version are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $user = $request->getAttribute('user');
            $user = $user instanceof User ? $user : null;
            $result = $this->contentVersioning->restoreToLiveContent($contentId, $version, $user);

            $response->getBody()->write(JsonHelper::encode([
                'success' => $result,
                'message' => $result ? 'Version restored successfully' : 'Failed to restore version'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /api/admin/versions/compare
     * Porovná dve verzie
     */
    public function compareVersions(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $contentId = $params['content_id'] ?? '';
        $version1 = (int)($params['version1'] ?? 0);
        $version2 = (int)($params['version2'] ?? 0);

        if (empty($contentId) || $version1 <= 0 || $version2 <= 0) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => 'Content ID and both versions are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $comparison = $this->versionManager->compareVersions($contentId, $version1, $version2);

            $response->getBody()->write(JsonHelper::encode([
                'success' => true,
                'data' => $comparison
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /api/admin/versions/stats
     * Získa štatistiky verzovania
     */
    public function getStats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $stats = $this->versionManager->getVersionStats();

            $response->getBody()->write(JsonHelper::encode([
                'success' => true,
                'data' => $stats
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * DELETE /api/admin/versions/{contentId}
     * Vymaže staré verzie
 * @param array<int|string, mixed> $args
 */public function cleanupVersions(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $contentId = $args['contentId'] ?? '';
        $params = $request->getQueryParams();
        $keep = (int)($params['keep'] ?? 10);

        try {
            $deleted = $this->versionManager->deleteVersions($contentId, $keep);

            $response->getBody()->write(JsonHelper::encode([
                'success' => true,
                'deleted' => $deleted,
                'message' => sprintf('Deleted %d old versions, kept %d', $deleted, $keep)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
