<?php
// backend/app/Http/Controllers/Admin/VersionController.php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Versioning\Services\EnhancedVersionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class VersionController
{
    private EnhancedVersionManager $versionManager;

    public function __construct(EnhancedVersionManager $versionManager)
    {
        $this->versionManager = $versionManager;
    }

    /**
     * GET /api/admin/versions/{contentId}
     * Získa históriu verzií pre obsah
     */
    public function getHistory(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $contentId = $args['contentId'] ?? '';

        try {
            $history = $this->versionManager->getVersionHistory($contentId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'content_id' => $contentId,
                    'versions' => $history,
                    'total' => count($history)
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /api/admin/versions/{contentId}/{version}
     * Získa konkrétnu verziu
     */
    public function getVersion(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $contentId = $args['contentId'] ?? '';
        $version = (int)($args['version'] ?? 0);

        try {
            $versionData = $this->versionManager->getVersion($contentId, $version);

            if (!$versionData) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Version not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $versionData->toArray()
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * POST /api/admin/versions/restore
     * Obnoví verziu
     */
    public function restoreVersion(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string)$request->getBody(), true);
        $contentId = $data['content_id'] ?? '';
        $version = (int)($data['version'] ?? 0);

        if (empty($contentId) || $version <= 0) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Content ID and version are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $result = $this->versionManager->restoreVersion($contentId, $version);

            $response->getBody()->write(json_encode([
                'success' => $result,
                'message' => $result ? 'Version restored successfully' : 'Failed to restore version'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
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
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Content ID and both versions are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $comparison = $this->versionManager->compareVersions($contentId, $version1, $version2);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $comparison
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
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

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $stats
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * DELETE /api/admin/versions/{contentId}
     * Vymaže staré verzie
     */
    public function cleanupVersions(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $contentId = $args['contentId'] ?? '';
        $params = $request->getQueryParams();
        $keep = (int)($params['keep'] ?? 10);

        try {
            $deleted = $this->versionManager->deleteVersions($contentId, $keep);

            $response->getBody()->write(json_encode([
                'success' => true,
                'deleted' => $deleted,
                'message' => sprintf('Deleted %d old versions, kept %d', $deleted, $keep)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
