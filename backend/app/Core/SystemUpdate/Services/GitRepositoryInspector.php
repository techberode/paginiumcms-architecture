<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

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
     * @return array{available: bool, describe: ?string, commit: ?string, branch: ?string, dirty: bool}
     */
    public function status(): array
    {
        $root = $this->resolveAppRoot();
        if ($root === null || !is_dir($root . '/.git')) {
            return [
                'available' => false,
                'describe' => null,
                'commit' => null,
                'branch' => null,
                'dirty' => false,
            ];
        }

        $describe = $this->runGit($root, 'describe --tags --always --dirty');
        $commit = $this->runGit($root, 'rev-parse --short HEAD');
        $branch = $this->runGit($root, 'rev-parse --abbrev-ref HEAD');

        return [
            'available' => true,
            'describe' => $describe !== '' ? $describe : null,
            'commit' => $commit !== '' ? $commit : null,
            'branch' => $branch !== '' && $branch !== 'HEAD' ? $branch : null,
            'dirty' => str_contains($describe, '-dirty'),
        ];
    }

    private function resolveAppRoot(): ?string
    {
        if ($this->appRoot !== null && $this->appRoot !== '') {
            $real = realpath($this->appRoot);

            return $real !== false ? $real : $this->appRoot;
        }

        $env = getenv('APP_ROOT') ?: ($_ENV['APP_ROOT'] ?? '');
        if (is_string($env) && $env !== '') {
            $real = realpath($env);

            return $real !== false ? $real : $env;
        }

        $candidate = realpath(dirname(__DIR__, 4));
        if ($candidate === false) {
            return null;
        }

        return is_dir($candidate . '/.git') ? $candidate : null;
    }

    private function runGit(string $root, string $args): string
    {
        $cmd = 'git -C ' . escapeshellarg($root) . ' ' . $args . ' 2>/dev/null';
        $output = shell_exec($cmd);

        return is_string($output) ? trim($output) : '';
    }
}
