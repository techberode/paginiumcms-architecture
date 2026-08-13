<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Versioning\Services\ContentVersioningService;
use PaginiumCMS\Core\Versioning\Services\EnhancedVersionManager;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class VersionController
{
    public function __construct(
        private EnhancedVersionManager $versionManager,
        private ContentVersioningService $contentVersioning,
        private JsonResponder $json
    ) {
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function getHistory(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $contentId = (string) ($args['contentId'] ?? '');

        try {
            $history = $this->versionManager->getVersionHistory($contentId);

            return $this->json->success($response, [
                'content_id' => $contentId,
                'versions' => $history,
                'total' => count($history),
            ]);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function getVersion(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $contentId = (string) ($args['contentId'] ?? '');
        $version = (int) ($args['version'] ?? 0);

        try {
            $versionData = $this->versionManager->getVersion($contentId, $version);

            if ($versionData === null) {
                return $this->json->error($response, 'Version not found', 404);
            }

            return $this->json->success($response, $versionData->toArray());
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function restoreVersion(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        $contentId = is_array($data) ? (string) ($data['content_id'] ?? '') : '';
        $version = is_array($data) ? (int) ($data['version'] ?? 0) : 0;

        if ($contentId === '' || $version <= 0) {
            return $this->json->error($response, 'Content ID and version are required', 400);
        }

        try {
            $user = $request->getAttribute('user');
            $user = $user instanceof User ? $user : null;
            $result = $this->contentVersioning->restoreToLiveContent($contentId, $version, $user);

            if (!$result) {
                return $this->json->error($response, 'Failed to restore version', 500);
            }

            return $this->json->success($response, null, 200, 'Version restored successfully');
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function compareVersions(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $contentId = (string) ($params['content_id'] ?? '');
        $version1 = (int) ($params['version1'] ?? 0);
        $version2 = (int) ($params['version2'] ?? 0);

        if ($contentId === '' || $version1 <= 0 || $version2 <= 0) {
            return $this->json->error($response, 'Content ID and both versions are required', 400);
        }

        try {
            $comparison = $this->versionManager->compareVersions($contentId, $version1, $version2);

            return $this->json->success($response, $comparison);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function getStats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            return $this->json->success($response, $this->versionManager->getVersionStats());
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function cleanupVersions(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $contentId = (string) ($args['contentId'] ?? '');
        $params = $request->getQueryParams();
        $keep = (int) ($params['keep'] ?? 10);

        try {
            $deleted = $this->versionManager->deleteVersions($contentId, $keep);

            return $this->json->success($response, [
                'deleted' => $deleted,
                'kept' => $keep,
            ], 200, sprintf('Deleted %d old versions, kept %d', $deleted, $keep));
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }
}
