<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Read-only GitHub API client for code deploy compare (It.63).
 */
final class GitHubReleaseClient
{
    private const API_BASE = 'https://api.github.com';

    /**
     * @param array<string, mixed> $settings systemUpdate group (githubOwner, githubRepo, githubToken, defaultBranch)
     * @return array<string, mixed>
     */
    public function check(array $settings, ?string $localCommit = null, ?string $localCommitFull = null): array
    {
        $owner = trim((string) ($settings['githubOwner'] ?? ''));
        $repo = trim((string) ($settings['githubRepo'] ?? ''));
        $token = trim((string) ($settings['githubToken'] ?? ''));
        $branch = trim((string) ($settings['defaultBranch'] ?? 'main'));
        if ($branch === '') {
            $branch = 'main';
        }

        if ($owner === '' || $repo === '') {
            return [
                'configured' => false,
                'error' => 'GitHub owner/repo not configured',
            ];
        }

        $base = self::API_BASE . '/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo);

        try {
            $latestCommit = $this->request(
                $base . '/commits/' . rawurlencode($branch),
                $token
            );
            $latestSha = is_string($latestCommit['sha'] ?? null) ? $latestCommit['sha'] : null;

            $latestRelease = $this->resolveLatestRelease($base, $token);

            $compare = null;
            $compareCommit = $localCommitFull ?? $localCommit;
            $latestTag = is_string($latestRelease['tag_name'] ?? null) ? $latestRelease['tag_name'] : null;
            $compareHead = $latestTag ?? $latestSha;
            if ($compareCommit !== null && $compareCommit !== '' && $compareHead !== null && $compareHead !== '') {
                $comparePath = $base . '/compare/' . rawurlencode($compareCommit) . '...' . rawurlencode($compareHead);
                $compare = $this->request($comparePath, $token);
            }

            $normalizedCompare = null;
            if (is_array($compare)) {
                $totalCommits = (int) ($compare['total_commits'] ?? 0);
                $commits = $this->normalizeCommits($compare);
                $normalizedCompare = [
                    'status' => $compare['status'] ?? null,
                    'ahead_by' => (int) ($compare['ahead_by'] ?? 0),
                    'behind_by' => (int) ($compare['behind_by'] ?? 0),
                    'total_commits' => $totalCommits,
                    'compare_head' => $compareHead,
                    'commits' => $commits,
                    'commits_truncated' => $totalCommits > count($commits),
                ];
            }

            $releaseBody = is_string($latestRelease['body'] ?? null) ? trim($latestRelease['body']) : '';
            if (strlen($releaseBody) > 8000) {
                $releaseBody = substr($releaseBody, 0, 8000) . "\n\n…";
            }

            return [
                'configured' => true,
                'owner' => $owner,
                'repo' => $repo,
                'default_branch' => $branch,
                'remote_commit' => $latestSha,
                'remote_commit_message' => is_string($latestCommit['commit']['message'] ?? null)
                    ? strtok($latestCommit['commit']['message'], "\n")
                    : null,
                'latest_release_tag' => is_string($latestRelease['tag_name'] ?? null) ? $latestRelease['tag_name'] : null,
                'latest_release_name' => is_string($latestRelease['name'] ?? null) ? $latestRelease['name'] : null,
                'latest_release_body' => $releaseBody !== '' ? $releaseBody : null,
                'latest_release_url' => is_string($latestRelease['html_url'] ?? null) ? $latestRelease['html_url'] : null,
                'latest_release_published_at' => is_string($latestRelease['published_at'] ?? null)
                    ? $latestRelease['published_at']
                    : null,
                'compare' => $normalizedCompare,
            ];
        } catch (RuntimeException $e) {
            return [
                'configured' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Latest release tag — includes pre-releases (beta.*) when GitHub has no stable /releases/latest.
     *
     * @return array<string, mixed>|null
     */
    private function resolveLatestRelease(string $base, string $token): ?array
    {
        try {
            $latest = $this->request($base . '/releases/latest', $token);
            if (is_string($latest['tag_name'] ?? null) && trim($latest['tag_name']) !== '') {
                return $latest;
            }
        } catch (RuntimeException) {
            // No stable latest — fall back to pre-releases / tag list.
        }

        try {
            $releaseList = self::coerceObjectList($this->request($base . '/releases?per_page=30', $token));
            $picked = self::pickLatestPublishedRelease($releaseList);
            if ($picked !== null) {
                return $picked;
            }
        } catch (RuntimeException) {
            // Optional — compare can still use branch HEAD.
        }

        try {
            $tagList = self::coerceObjectList($this->request($base . '/tags?per_page=1', $token));
            $firstTag = $tagList[0] ?? null;
            if ($firstTag !== null) {
                $tagName = trim((string) ($firstTag['name'] ?? ''));
                if ($tagName !== '') {
                    return ['tag_name' => $tagName];
                }
            }
        } catch (RuntimeException) {
            return null;
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function coerceObjectList(mixed $raw): array
    {
        if (!is_array($raw) || !array_is_list($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $releases
     * @return array<string, mixed>|null
     */
    public static function pickLatestPublishedRelease(array $releases): ?array
    {
        foreach ($releases as $release) {
            if (($release['draft'] ?? false) === true) {
                continue;
            }
            if (is_string($release['tag_name'] ?? null) && trim($release['tag_name']) !== '') {
                return $release;
            }
        }

        return null;
    }

    /**
     * Live probe for admin UI — validates token can read the configured repository.
     *
     * @return array{ok: bool, http_status: int, error: ?string}
     */
    public function probeRepositoryAccess(string $owner, string $repo, string $token): array
    {
        $owner = trim($owner);
        $repo = trim($repo);
        if ($owner === '' || $repo === '') {
            return [
                'ok' => false,
                'http_status' => 0,
                'error' => 'GitHub owner or repository is not configured',
            ];
        }

        if ($token === '') {
            return [
                'ok' => false,
                'http_status' => 0,
                'error' => 'GitHub token is missing',
            ];
        }

        $url = self::API_BASE . '/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo);

        try {
            $this->request($url, $token);

            return ['ok' => true, 'http_status' => 200, 'error' => null];
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            $http = 0;
            if (preg_match('/HTTP (\d+)/', $message, $m) === 1) {
                $http = (int) $m[1];
            }

            $error = match ($http) {
                401 => 'GitHub rejected the token (invalid or expired)',
                403 => 'Token lacks permission to read this repository (scope or SSO authorization)',
                404 => 'Repository not found — check owner/repo settings',
                default => $message,
            };

            return ['ok' => false, 'http_status' => $http, 'error' => $error];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $url, string $token): array
    {
        OutboundUrlGuard::fromEnv()->assertAllowed($url);

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('GitHub request init failed');
        }

        $headers = [
            'Accept: application/vnd.github+json',
            'User-Agent: PaginiumCMS-SystemUpdate',
        ];
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ]);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);

        if (!is_string($body)) {
            throw new RuntimeException('GitHub request failed: ' . ($err ?: 'unknown'));
        }

        if ($code >= 400) {
            throw new RuntimeException('GitHub API HTTP ' . $code);
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $compare
     * @return list<array{sha: string, sha_full: string, message: string, author: ?string, date: ?string, url: ?string}>
     */
    private function normalizeCommits(array $compare, int $limit = 50): array
    {
        $rows = $compare['commits'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach (array_slice($rows, 0, $limit) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $shaFull = is_string($row['sha'] ?? null) ? $row['sha'] : '';
            if ($shaFull === '') {
                continue;
            }
            $commit = is_array($row['commit'] ?? null) ? $row['commit'] : [];
            $messageRaw = is_string($commit['message'] ?? null) ? $commit['message'] : '';
            $message = '';
            if ($messageRaw !== '') {
                $lines = explode("\n", $messageRaw, 2);
                $message = $lines[0];
            }
            if (strlen($message) > 200) {
                $message = substr($message, 0, 200) . '…';
            }
            $author = is_array($commit['author'] ?? null) ? $commit['author'] : [];
            $authorName = is_string($author['name'] ?? null) ? $author['name'] : null;
            $date = is_string($author['date'] ?? null) ? $author['date'] : null;
            $url = is_string($row['html_url'] ?? null) ? $row['html_url'] : null;

            $out[] = [
                'sha' => substr($shaFull, 0, 7),
                'sha_full' => $shaFull,
                'message' => $message,
                'author' => $authorName,
                'date' => $date,
                'url' => $url,
            ];
        }

        return $out;
    }
}
