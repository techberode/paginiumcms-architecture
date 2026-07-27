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

            $latestRelease = null;
            try {
                $latestRelease = $this->request($base . '/releases/latest', $token);
            } catch (RuntimeException) {
                // No releases yet — optional for compare.
            }

            $compare = null;
            $compareCommit = $localCommitFull ?? $localCommit;
            if ($compareCommit !== null && $compareCommit !== '' && $latestSha !== null) {
                $comparePath = $base . '/compare/' . rawurlencode($compareCommit) . '...' . rawurlencode($latestSha);
                $compare = $this->request($comparePath, $token);
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
                'compare' => $compare !== null ? [
                    'status' => $compare['status'] ?? null,
                    'ahead_by' => (int) ($compare['ahead_by'] ?? 0),
                    'behind_by' => (int) ($compare['behind_by'] ?? 0),
                    'total_commits' => (int) ($compare['total_commits'] ?? 0),
                ] : null,
            ];
        } catch (RuntimeException $e) {
            return [
                'configured' => true,
                'error' => $e->getMessage(),
            ];
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
        curl_close($ch);

        if (!is_string($body)) {
            throw new RuntimeException('GitHub request failed: ' . ($err ?: 'unknown'));
        }

        if ($code >= 400) {
            throw new RuntimeException('GitHub API HTTP ' . $code);
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
