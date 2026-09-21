<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Playground;

use RuntimeException;

/**
 * Maps a configured Git HTTPS URL + ref to a zipball/archive URL (It.95d).
 * Token never goes in the URL (OutboundUrlGuard blocks userinfo).
 */
final class PlaygroundGitArchiveLocator
{
    public static function zipUrl(string $repoUrl, string $ref): string
    {
        $url = trim($repoUrl);
        $pin = self::normalizeRef($ref);
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new RuntimeException('Invalid Git repository URL.');
        }
        if (
            strtolower((string) $parts['scheme']) !== 'https'
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            throw new RuntimeException('Git repository URL must use HTTPS without embedded credentials.');
        }

        $path = trim((string) ($parts['path'] ?? ''), '/');
        $lowerPath = strtolower($path);
        if (str_ends_with($lowerPath, '.zip')) {
            return $url;
        }

        $host = strtolower((string) $parts['host']);
        $path = (string) preg_replace('/\.git$/i', '', $path);

        if ($host === 'github.com' || $host === 'www.github.com') {
            [$owner, $repo] = self::githubOwnerRepo($path);

            return 'https://api.github.com/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/zipball/' . rawurlencode($pin);
        }

        if ($host === 'api.github.com') {
            return $url;
        }

        if ($host === 'gitlab.com') {
            if ($path === '' || str_contains($path, '..')) {
                throw new RuntimeException('Invalid GitLab project path.');
            }

            return 'https://' . $host . '/api/v4/projects/' . rawurlencode($path)
                . '/repository/archive.zip?sha=' . rawurlencode($pin);
        }

        throw new RuntimeException('Unsupported git host. Use GitHub, GitLab.com, or a direct HTTPS .zip URL.');
    }

    public static function normalizeRef(string $ref): string
    {
        $pin = trim($ref);
        if ($pin === '') {
            $pin = 'main';
        }
        if (preg_match('#^[A-Za-z0-9._/-]{1,200}$#', $pin) !== 1 || str_contains($pin, '..')) {
            throw new RuntimeException('Invalid Git ref.');
        }

        return $pin;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function githubOwnerRepo(string $path): array
    {
        $segments = array_values(array_filter(explode('/', $path), static fn (string $part): bool => $part !== ''));
        if (count($segments) < 2) {
            throw new RuntimeException('GitHub URL must be owner/repo.');
        }

        $owner = $segments[0];
        $repo = $segments[1];
        if (preg_match('/^[A-Za-z0-9._-]+$/', $owner) !== 1 || preg_match('/^[A-Za-z0-9._-]+$/', $repo) !== 1) {
            throw new RuntimeException('Invalid GitHub owner or repository name.');
        }

        return [$owner, $repo];
    }
}
