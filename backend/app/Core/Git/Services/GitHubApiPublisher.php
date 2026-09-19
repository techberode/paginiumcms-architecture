<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\Git\Contracts\GitHubApiTransport;
use PaginiumCMS\Core\Git\Contracts\GitPublisherInterface;
use PaginiumCMS\Core\Git\Models\GitPublishResult;
use RuntimeException;

/**
 * GitHub Contents / Git Data API publisher — no local git binary (It.70).
 *
 * SSOT files are read from the CMS disk; the remote commit is created via the API.
 * Updating the branch ref is the push.
 */
final class GitHubApiPublisher implements GitPublisherInterface
{
    private const API_BASE = 'https://api.github.com';

    /** @var list<string> */
    private array $staged = [];

    private ?string $lastCommitHash = null;

    public function __construct(
        private GitPublishSettings $gitSettings,
        private GitPathValidator $paths,
        private FileReaderInterface $reader,
        private GitHubApiTransport $transport,
    ) {
    }

    public function status(): array
    {
        $repo = $this->gitSettings->githubRepository();
        $token = $this->gitSettings->githubToken();
        $branch = $this->gitSettings->branch();

        if ($repo === '' || $token === '') {
            return [
                'publisher' => 'github_api',
                'repositoryConfigured' => false,
                'branch' => $branch,
            ];
        }

        try {
            $ref = $this->request('GET', $this->repoUrl() . '/git/ref/heads/' . rawurlencode($branch));
            $sha = $this->objectSha($ref);

            return [
                'publisher' => 'github_api',
                'repositoryConfigured' => true,
                'head' => $sha,
                'branch' => $branch,
                'repository' => $repo,
            ];
        } catch (\Throwable $e) {
            return [
                'publisher' => 'github_api',
                'repositoryConfigured' => true,
                'branch' => $branch,
                'repository' => $repo,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function stage(array $relativePaths): void
    {
        foreach ($relativePaths as $path) {
            $safe = $this->paths->assertStageableRelativePath($path);
            if (!in_array($safe, $this->staged, true)) {
                $this->staged[] = $safe;
            }
        }
    }

    public function commit(string $message): GitPublishResult
    {
        $message = trim($message);
        if ($message === '') {
            throw new RuntimeException('Commit message is required.');
        }

        if ($this->staged === []) {
            return new GitPublishResult(true, 'stored', null, 'Nothing to commit');
        }

        $this->assertConfigured();

        $entries = [];
        foreach ($this->staged as $path) {
            if (!$this->reader->exists($path)) {
                throw new RuntimeException('SSOT file missing for Git publish: ' . $path);
            }
            $bytes = $this->reader->readBinary($path);
            $blob = $this->request('POST', $this->repoUrl() . '/git/blobs', [
                'content' => base64_encode($bytes),
                'encoding' => 'base64',
            ]);
            $blobSha = isset($blob['sha']) && is_string($blob['sha']) ? $blob['sha'] : '';
            if ($blobSha === '') {
                throw new RuntimeException('GitHub blob create returned no sha.');
            }
            $entries[] = [
                'path' => $path,
                'mode' => '100644',
                'type' => 'blob',
                'sha' => $blobSha,
            ];
        }

        $branch = $this->gitSettings->branch();
        $ref = $this->request('GET', $this->repoUrl() . '/git/ref/heads/' . rawurlencode($branch));
        $parentSha = $this->objectSha($ref);
        if ($parentSha === '') {
            throw new RuntimeException('GitHub branch ref has no commit sha.');
        }

        $parent = $this->request('GET', $this->repoUrl() . '/git/commits/' . rawurlencode($parentSha));
        $baseTree = '';
        if (isset($parent['tree']) && is_array($parent['tree']) && isset($parent['tree']['sha']) && is_string($parent['tree']['sha'])) {
            $baseTree = $parent['tree']['sha'];
        }

        $treePayload = ['tree' => $entries];
        if ($baseTree !== '') {
            $treePayload['base_tree'] = $baseTree;
        }
        $tree = $this->request('POST', $this->repoUrl() . '/git/trees', $treePayload);
        $treeSha = isset($tree['sha']) && is_string($tree['sha']) ? $tree['sha'] : '';
        if ($treeSha === '') {
            throw new RuntimeException('GitHub tree create returned no sha.');
        }

        $commit = $this->request('POST', $this->repoUrl() . '/git/commits', [
            'message' => $message,
            'tree' => $treeSha,
            'parents' => [$parentSha],
        ]);
        $commitSha = isset($commit['sha']) && is_string($commit['sha']) ? $commit['sha'] : '';
        if ($commitSha === '') {
            throw new RuntimeException('GitHub commit create returned no sha.');
        }

        $this->request('PATCH', $this->repoUrl() . '/git/refs/heads/' . rawurlencode($branch), [
            'sha' => $commitSha,
        ]);

        $this->lastCommitHash = $commitSha;
        $this->staged = [];

        return new GitPublishResult(true, 'committed', $commitSha, 'Commit created');
    }

    public function push(): GitPublishResult
    {
        if ($this->lastCommitHash === null) {
            return new GitPublishResult(true, 'committed', null, 'Nothing to push');
        }

        $hash = $this->lastCommitHash;
        $this->lastCommitHash = null;

        return new GitPublishResult(true, 'pushed', $hash, 'Push completed');
    }

    private function assertConfigured(): void
    {
        if ($this->gitSettings->githubRepository() === '' || $this->gitSettings->githubToken() === '') {
            throw new RuntimeException('GitHub API publisher is not configured (repository and token required).');
        }
        $this->paths->assertSafeRef($this->gitSettings->branch(), 'branch');
    }

    private function repoUrl(): string
    {
        $parsed = $this->gitSettings->githubOwnerRepo();

        return self::API_BASE . '/repos/' . rawurlencode($parsed['owner']) . '/' . rawurlencode($parsed['repo']);
    }

    /**
     * @param array<string, mixed>|null $body
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $url, ?array $body = null): array
    {
        return $this->transport->request($method, $url, $this->gitSettings->githubToken(), $body);
    }

    /**
     * @param array<string, mixed> $ref
     */
    private function objectSha(array $ref): string
    {
        $object = $ref['object'] ?? null;
        if (is_array($object) && isset($object['sha']) && is_string($object['sha'])) {
            return $object['sha'];
        }

        return isset($ref['sha']) && is_string($ref['sha']) ? $ref['sha'] : '';
    }
}
