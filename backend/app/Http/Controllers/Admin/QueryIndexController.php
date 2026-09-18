<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexAdminService;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexInterface;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin query index management (It.92e).
 */
final class QueryIndexController
{
    public function __construct(
        private QueryIndexAdminService $admin,
        private SecurityLogger $securityLogger,
        private JsonResponder $json
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->admin->status());
    }

    public function rebuild(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $result = $this->admin->rebuild();
        } catch (InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        }

        $this->audit($request, 'query_index.rebuild', (string) $result['entries']);

        return $this->json->success($response, $result, 200, 'SQLite query index rebuilt from JSON catalog.');
    }

    public function activate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = RequestJsonBody::decode($request);
        $driver = is_array($body) ? (string) ($body['driver'] ?? '') : '';

        try {
            $result = $this->admin->activateDriver($driver);
        } catch (InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        }

        $this->audit($request, 'query_index.activate', $driver);

        $message = $driver === QueryIndexInterface::DRIVER_SQLITE
            ? 'SQLite query index driver enabled.'
            : 'Query index driver set to JSON.';

        return $this->json->success($response, $result, 200, $message);
    }

    private function audit(ServerRequestInterface $request, string $action, string $detail): void
    {
        $user = $request->getAttribute('user');
        if ($user instanceof User) {
            $this->securityLogger->logSuspiciousActivity(
                $action,
                sprintf('User %s: %s', $user->getId(), $detail)
            );
        }
    }
}
