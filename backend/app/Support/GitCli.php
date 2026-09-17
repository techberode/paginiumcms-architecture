<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

/**
 * Git CLI prefix for bind-mounted checkouts (Docker www-data vs host UID → dubious ownership).
 */
final class GitCli
{
    /**
     * Returns `git -c safe.directory=… -C …` for the given repository root.
     */
    public static function at(string $repoRoot): string
    {
        $resolved = realpath($repoRoot);
        $root = $resolved !== false ? $resolved : $repoRoot;

        return 'git -c safe.directory=' . escapeshellarg($root)
            . ' -C ' . escapeshellarg($root);
    }
}
