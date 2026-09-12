<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

/**
 * Compares local CMS version with GitHub latest release (It.63 v2).
 */
final class SystemUpdateVersionMatcher
{
    /**
     * @return array{
     *     status: 'current'|'update_available'|'unknown',
     *     current_version: string,
     *     latest_version: ?string,
     *     current_tag: ?string,
     *     latest_tag: ?string
     * }
     */
    public function evaluate(
        string $currentAppVersion,
        ?string $gitDescribe,
        ?string $latestReleaseTag,
        ?string $localCommit,
        ?string $remoteCommit,
        ?int $behindBy = null
    ): array {
        $currentTag = $this->tagFromDescribe($gitDescribe) ?? $this->normalizeVersion($currentAppVersion);
        $latestTag = $latestReleaseTag !== null && $latestReleaseTag !== ''
            ? $this->normalizeVersion($latestReleaseTag)
            : null;

        $status = 'unknown';

        if ($latestTag !== null && $latestTag !== '') {
            if ($currentTag === $latestTag) {
                $status = 'current';
            } elseif ($currentTag !== '' && $this->isSemverNewer($latestTag, $currentTag)) {
                $status = 'update_available';
            } else {
                // Local checkout is ahead of GitHub "latest release" (e.g. beta.69 deployed, beta.68 still "latest").
                $status = 'current';
            }
        } elseif ($localCommit !== null && $remoteCommit !== null && $localCommit !== '' && $remoteCommit !== '') {
            if ($this->commitsMatch($localCommit, $remoteCommit)) {
                $status = 'current';
            } elseif ($behindBy !== null && $behindBy > 0) {
                $status = 'update_available';
            } elseif ($behindBy === 0) {
                $status = 'current';
            }
        }

        return [
            'status' => $status,
            'current_version' => $currentAppVersion,
            'latest_version' => $latestTag !== null ? $latestTag : null,
            'current_tag' => $currentTag !== '' ? $currentTag : null,
            'latest_tag' => $latestTag,
        ];
    }

    private function normalizeVersion(string $version): string
    {
        $version = trim($version);
        if (str_starts_with($version, 'v') || str_starts_with($version, 'V')) {
            $version = substr($version, 1);
        }

        return $version;
    }

    private function tagFromDescribe(?string $describe): ?string
    {
        if ($describe === null || $describe === '') {
            return null;
        }

        if (preg_match('/^(v?\d+\.\d+\.\d+(?:-[a-zA-Z0-9.]+)?)/', $describe, $matches) === 1) {
            return $this->normalizeVersion($matches[1]);
        }

        return null;
    }

    private function commitsMatch(string $local, string $remote): bool
    {
        $local = strtolower(trim($local));
        $remote = strtolower(trim($remote));

        if ($local === $remote) {
            return true;
        }

        return str_starts_with($remote, $local) || str_starts_with($local, $remote);
    }

    private function isSemverNewer(string $candidate, string $baseline): bool
    {
        $candidate = $this->normalizeVersion($candidate);
        $baseline = $this->normalizeVersion($baseline);

        if ($candidate === '' || $baseline === '') {
            return false;
        }

        return version_compare($candidate, $baseline, '>');
    }
}
