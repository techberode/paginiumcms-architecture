<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

use PaginiumCMS\Support\AppRoot;

/**
 * Read-only local git metadata for admin system update (It.63).
 */
final class GitRepositoryInspector
{
    public function __construct(
        private ?string $appRoot = null
    ) {
    }

    /**
     * @return array{available: bool, describe: ?string, commit: ?string, commit_full: ?string, branch: ?string, dirty: bool}
     */
    public function status(): array
    {
        $root = $this->resolveAppRoot();
        if ($root === null || !is_dir($root . '/.git')) {
            return [
                'available' => false,
                'describe' => null,
                'commit' => null,
                'commit_full' => null,
                'branch' => null,
                'dirty' => false,
            ];
        }

        $describe = $this->runGit($root, 'describe --tags --always --dirty');
        $commit = $this->runGit($root, 'rev-parse --short HEAD');
        $commitFull = $this->runGit($root, 'rev-parse HEAD');
        $branch = $this->runGit($root, 'rev-parse --abbrev-ref HEAD');

        return [
            'available' => true,
            'describe' => $describe !== '' ? $describe : null,
            'commit' => $commit !== '' ? $commit : null,
            'commit_full' => $commitFull !== '' ? $commitFull : null,
            'branch' => $branch !== '' && $branch !== 'HEAD' ? $branch : null,
            'dirty' => str_contains($describe, '-dirty'),
        ];
    }

    private function resolveAppRoot(): ?string
    {
        $root = AppRoot::resolve($this->appRoot);
        if ($root === null || !is_dir($root . '/.git')) {
            return null;
        }

        return $root;
    }

    private function runGit(string $root, string $args): string
    {
        $cmd = 'git -C ' . escapeshellarg($root) . ' ' . $args . ' 2>/dev/null';
        $output = shell_exec($cmd);

        return is_string($output) ? trim($output) : '';
    }
}
