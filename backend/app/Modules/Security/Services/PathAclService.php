<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Exception\AuthorizationException;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Path-level ACL checks layered on top of RBAC (Iteration 11).
 */
final class PathAclService
{
    public function __construct(
        private AclRepository $acl,
        private AuthorizationInterface $authorization
    ) {
    }

    public function canAccessPath(User $user, string $path, ?string $permission = null): bool
    {
        if (!$this->acl->isEnabled()) {
            return true;
        }

        if (in_array('SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $path = $this->normalizePath($path);
        if ($path === '') {
            return true;
        }

        $matchedRule = null;
        foreach ($this->acl->rules() as $rule) {
            if ($rule['enabled'] !== true) {
                continue;
            }

            if (!$this->pathMatches($path, $rule['path'])) {
                continue;
            }

            $matchedRule = $rule;
            break;
        }

        if ($matchedRule === null) {
            return true;
        }

        $allowedRoles = $matchedRule['roles'];
        if ($allowedRoles !== []) {
            foreach ($allowedRoles as $role) {
                if ($this->authorization->hasRole($user, $role)) {
                    return true;
                }
            }

            return false;
        }

        $requiredPermissions = $matchedRule['permissions'];
        if ($requiredPermissions === []) {
            return true;
        }

        foreach ($requiredPermissions as $requiredPermission) {
            if ($this->authorization->hasPermission($user, $requiredPermission)) {
                return true;
            }
        }

        if ($permission !== null && $this->authorization->hasPermission($user, $permission)) {
            return true;
        }

        return false;
    }

    public function requirePathAccess(User $user, string $path, ?string $permission = null): void
    {
        if (!$this->canAccessPath($user, $path, $permission)) {
            throw new AuthorizationException(sprintf('ACL denied for path: %s', $path));
        }
    }

    private function normalizePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        return $path;
    }

    private function pathMatches(string $path, string $pattern): bool
    {
        $pattern = trim(str_replace('\\', '/', $pattern), '/');
        if ($pattern === '') {
            return false;
        }

        if ($pattern === $path) {
            return true;
        }

        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim(substr($pattern, 0, -1), '/');

            return $prefix === '' || str_starts_with($path, $prefix);
        }

        return false;
    }
}
