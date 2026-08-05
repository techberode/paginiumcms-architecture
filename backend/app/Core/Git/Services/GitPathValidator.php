<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * Validates Git configuration and stageable content paths (Iteration 70).
 */
final class GitPathValidator
{
    /** @var list<string> */
    private const STAGEABLE_PREFIXES = [
        'pages/',
        'blog/',
    ];

    public function assertSafeRef(string $ref, string $label = 'ref'): string
    {
        $ref = trim($ref);
        if ($ref === '' || strlen($ref) > 100) {
            throw new InvalidArgumentException(sprintf('Invalid Git %s.', $label));
        }

        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $ref)) {
            throw new InvalidArgumentException(sprintf('Git %s contains forbidden characters.', $label));
        }

        return $ref;
    }

    public function resolveRepositoryPath(string $configuredPath): string
    {
        $configuredPath = trim($configuredPath);
        if ($configuredPath === '') {
            throw new RuntimeException('Git repository path is not configured.');
        }

        $real = realpath($configuredPath);
        if ($real === false || !is_dir($real)) {
            throw new RuntimeException('Git repository path does not exist.');
        }

        return $real;
    }

    public function assertStageableRelativePath(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', trim($relativePath, '/'));
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            throw new InvalidArgumentException('Stage path is not allow-listed.');
        }

        foreach (self::STAGEABLE_PREFIXES as $prefix) {
            if (str_starts_with($relativePath, $prefix)) {
                return $relativePath;
            }
        }

        throw new InvalidArgumentException('Stage path is not allow-listed.');
    }

    /**
     * CMS content paths (pages/, blog/) are staged relative to the configured repository root.
     */
    public function normalizeContentPath(string $contentPath): string
    {
        return $this->assertStageableRelativePath(str_replace('\\', '/', ltrim($contentPath, '/')));
    }
}
