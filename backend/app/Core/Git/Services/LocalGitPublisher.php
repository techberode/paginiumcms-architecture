<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Services;

use PaginiumCMS\Core\Git\Contracts\GitPublisherInterface;
use PaginiumCMS\Core\Git\Models\GitPublishResult;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use RuntimeException;

/**
 * Local git binary publisher with allow-listed arguments (Iteration 70).
 */
final class LocalGitPublisher implements GitPublisherInterface
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private LocalGitProcess $process,
        private GitPathValidator $paths,
    ) {
    }

    public function status(): array
    {
        $repo = $this->repositoryPath();
        $head = $this->runOrFail(['rev-parse', 'HEAD'], $repo, allowEmpty: true);
        $porcelain = $this->runOrFail(['status', '--porcelain'], $repo, allowEmpty: true);

        return [
            'publisher' => 'local',
            'repositoryConfigured' => true,
            'head' => trim($head['stdout']) !== '' ? trim($head['stdout']) : null,
            'dirtyCount' => $this->countPorcelainLines($porcelain['stdout']),
            'branch' => $this->configuredBranch(),
            'remote' => $this->configuredRemote(),
        ];
    }

    public function stage(array $relativePaths): void
    {
        if ($relativePaths === []) {
            return;
        }

        $repo = $this->repositoryPath();
        $args = ['add', '--'];
        foreach ($relativePaths as $path) {
            $args[] = $this->paths->assertStageableRelativePath($path);
        }

        $this->runOrFail($args, $repo);
    }

    public function commit(string $message): GitPublishResult
    {
        $repo = $this->repositoryPath();
        $message = trim($message);
        if ($message === '') {
            throw new RuntimeException('Commit message is required.');
        }

        $result = $this->runOrFail(['commit', '-m', $message], $repo, allowEmpty: true);
        if ($result['exitCode'] !== 0 && str_contains($result['stdout'] . $result['stderr'], 'nothing to commit')) {
            return new GitPublishResult(true, 'stored', null, 'Nothing to commit');
        }

        if ($result['exitCode'] !== 0) {
            return new GitPublishResult(false, 'publish_failed', null, null, trim($result['stderr'] ?: $result['stdout']));
        }

        $hash = trim($this->runOrFail(['rev-parse', 'HEAD'], $repo)['stdout']);

        return new GitPublishResult(true, 'committed', $hash !== '' ? $hash : null, 'Commit created');
    }

    public function push(): GitPublishResult
    {
        if (!$this->pushEnabled()) {
            return new GitPublishResult(true, 'committed', null, 'Push disabled');
        }

        $repo = $this->repositoryPath();
        $remote = $this->paths->assertSafeRef($this->configuredRemote(), 'remote');
        $branch = $this->paths->assertSafeRef($this->configuredBranch(), 'branch');

        $result = $this->runOrFail(['push', $remote, $branch], $repo, allowEmpty: true);
        if ($result['exitCode'] !== 0) {
            return new GitPublishResult(false, 'publish_failed', null, null, trim($result['stderr'] ?: $result['stdout']));
        }

        return new GitPublishResult(true, 'pushed', null, 'Push completed');
    }

    /**
     * @param list<string> $args
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    private function runOrFail(array $args, string $repo, bool $allowEmpty = false): array
    {
        $result = $this->process->run($args, $repo);
        if (!$allowEmpty && $result['exitCode'] !== 0) {
            throw new RuntimeException(trim($result['stderr'] ?: $result['stdout']) ?: 'Git command failed');
        }

        return $result;
    }

    private function repositoryPath(): string
    {
        $engine = $this->settings->group('engine');

        return $this->paths->resolveRepositoryPath((string) ($engine['gitRepositoryPath'] ?? ''));
    }

    private function configuredRemote(): string
    {
        $engine = $this->settings->group('engine');

        return (string) ($engine['gitRemote'] ?? 'origin');
    }

    private function configuredBranch(): string
    {
        $engine = $this->settings->group('engine');

        return (string) ($engine['gitBranch'] ?? 'main');
    }

    private function pushEnabled(): bool
    {
        $engine = $this->settings->group('engine');

        return (bool) ($engine['gitPushEnabled'] ?? false);
    }

    private function countPorcelainLines(string $stdout): int
    {
        $lines = array_filter(array_map('trim', explode("\n", $stdout)));

        return count($lines);
    }
}
