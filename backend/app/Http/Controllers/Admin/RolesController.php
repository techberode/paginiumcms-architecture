<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\BulkIdsParser;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Security\Models\RoleRecord;
use PaginiumCMS\Modules\Security\PermissionCatalog;
use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PaginiumCMS\Modules\Security\Services\RoleCatalogSeeder;
use PaginiumCMS\Modules\Security\Services\RoleRepository;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Custom RBAC roles API (It.84d) — SUPER_ADMIN only.
 */
final class RolesController
{
    public function __construct(
        private RoleRepository $roles,
        private RoleCatalogSeeder $seeder,
        private UserRepository $users,
        private AuthorizationManager $authorization,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->seeder->seedIfEmpty();

        return $this->json->success($response, [
            'roles' => $this->roles->list(),
            'permissions' => PermissionCatalog::ALL,
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->seeder->seedIfEmpty();

        $data = RequestJsonBody::decode($request);
        $id = RoleRecord::normalizeId((string) ($data['id'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        $permissions = is_array($data['permissions'] ?? null)
            ? array_values(array_map(static fn (mixed $entry): string => (string) $entry, $data['permissions']))
            : [];

        if ($id === '' || RoleRecord::isReservedId($id)) {
            return $this->json->validation($response, 'Invalid role id.', ['id' => ['Invalid role id']]);
        }

        if ($this->roles->exists($id)) {
            return $this->json->error($response, 'Role already exists', 409);
        }

        try {
            $record = $this->roles->save($id, $name, $permissions, false);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }

        $this->authorization->reloadFromRoles();

        return $this->json->success($response, $record->toArray(), 201, 'Role created');
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->seeder->seedIfEmpty();

        $id = RoleRecord::normalizeId((string) ($args['id'] ?? ''));
        $existing = $id !== '' ? $this->roles->get($id) : null;
        if ($existing === null) {
            return $this->json->error($response, 'Role not found', 404);
        }

        $data = RequestJsonBody::decode($request) ?? [];

        $name = array_key_exists('name', $data)
            ? trim((string) $data['name'])
            : $existing->name;
        $permissions = array_key_exists('permissions', $data)
            ? (is_array($data['permissions'])
                ? array_values(array_map(static fn (mixed $entry): string => (string) $entry, $data['permissions']))
                : [])
            : $existing->permissions;

        try {
            $record = $this->roles->save($id, $name, $permissions, $existing->system);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }

        $this->authorization->reloadFromRoles();

        return $this->json->success($response, $record->toArray(), 200, 'Role updated');
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = RoleRecord::normalizeId((string) ($args['id'] ?? ''));
        if ($id === '' || $this->roles->get($id) === null) {
            return $this->json->error($response, 'Role not found', 404);
        }

        if ($this->countUsersWithRole($id) > 0) {
            return $this->json->error($response, 'Role is assigned to users and cannot be deleted', 422);
        }

        try {
            $this->roles->delete($id);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }

        $this->authorization->reloadFromRoles();

        return $this->json->success($response, ['id' => $id, 'removed' => true]);
    }

    public function bulkDelete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ids = BulkIdsParser::fromRequest($request);
        if ($ids === []) {
            return $this->json->error($response, 'No roles selected', 400);
        }

        $batch = new BulkBatchResult();
        foreach ($ids as $id) {
            $normalized = RoleRecord::normalizeId($id);
            if ($normalized === '' || $this->roles->get($normalized) === null) {
                $batch->addFailure($id, 'Role not found');
                continue;
            }

            if ($this->countUsersWithRole($normalized) > 0) {
                $batch->addFailure($normalized, 'Role is assigned to users');
                continue;
            }

            try {
                $this->roles->delete($normalized);
                $batch->addSuccess($normalized);
            } catch (RuntimeException $exception) {
                $batch->addFailure($normalized, $exception->getMessage());
            }
        }

        $this->authorization->reloadFromRoles();

        return $this->json->success($response, $batch->toArray(), 200, 'Roles deleted');
    }

    private function countUsersWithRole(string $roleId): int
    {
        $count = 0;
        foreach ($this->users->findAll() as $user) {
            if (in_array($roleId, $user->getRoles(), true)) {
                ++$count;
            }
        }

        return $count;
    }
}
