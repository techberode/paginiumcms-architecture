<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use InvalidArgumentException;
use PaginiumCMS\Core\Cache\Services\CacheAdminService;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Manuálna správa cache z administrácie.
 *
 *  GET  /api/admin/cache       – stav cache
 *  POST /api/admin/cache/purge – vymazanie (scope: content | all)
 */
final class CacheController
{
    public function __construct(
        private CacheAdminService $cacheAdmin,
        private SecurityLogger $securityLogger,
        private JsonResponder $json
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->cacheAdmin->stats());
    }

    public function purge(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = RequestJsonBody::decode($request);
        $scope = is_array($body) && isset($body['scope'])
            ? (string) $body['scope']
            : CacheAdminService::SCOPE_CONTENT;

        try {
            $result = $this->cacheAdmin->purge($scope);
        } catch (InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        }

        $user = $request->getAttribute('user');
        if ($user instanceof User) {
            $this->securityLogger->logCachePurge($user, $scope, $result);
        }

        $message = $scope === CacheAdminService::SCOPE_ALL
            ? 'Celá cache bola vymazaná'
            : 'Cache obsahu bola invalidovaná';

        return $this->json->success($response, $result, 200, $message);
    }
}
