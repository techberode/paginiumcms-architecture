<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Exception\AuthorizationException;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Enforces path-level ACL on flat-file content and media paths (audit S9).
 */
final class ContentPathAclGuard
{
    public function __construct(private PathAclService $pathAcl)
    {
    }

    public function canAccess(?User $user, string $storageRelativePath, ?string $permission = null): bool
    {
        return $this->pathAcl->canAccessPath(
            $user ?? new User(),
            $storageRelativePath,
            $permission
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function requireAccess(?User $user, string $storageRelativePath, ?string $permission = null): void
    {
        $this->pathAcl->requirePathAccess(
            $user ?? new User(),
            $storageRelativePath,
            $permission
        );
    }

    public function contentPathFromSlug(string $type, string $slug): string
    {
        $directory = $type === 'article' ? 'blog' : 'pages';

        return $directory . '/' . $slug;
    }

    public function mediaFolderPath(string $folder): string
    {
        $folder = trim(str_replace('\\', '/', $folder), '/');

        return $folder === '' ? 'media' : 'media/' . $folder;
    }
}
