<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Probes local Git publish capability for admin settings (Iteration 70).
 */
final class GitCapabilityProbe
{
    public function __construct(
        private GitPublishSettings $gitSettings,
        private LocalGitProcess $process,
        private GitPathValidator $paths,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    public function probe(): array
    {
        if (!$this->gitSettings->isEnabled()) {
            return [
                'status' => 'disabled',
                'message' => 'Git publish distribution is disabled.',
                'details' => ['strategy' => 'disabled'],
            ];
        }

        if (!$this->process->isAvailable()) {
            return [
                'status' => 'unavailable',
                'message' => 'Git binary is not available on the server PATH.',
                'details' => ['strategy' => $this->gitSettings->strategy()],
            ];
        }

        $engine = $this->settings->group('engine');
        $repoPath = trim((string) ($engine['gitRepositoryPath'] ?? ''));
        if ($repoPath === '') {
            return [
                'status' => 'misconfigured',
                'message' => 'Git repository path is not configured.',
                'details' => ['strategy' => $this->gitSettings->strategy()],
            ];
        }

        try {
            $resolved = $this->paths->resolveRepositoryPath($repoPath);
            $isRepo = is_dir($resolved . '/.git');

            return [
                'status' => $isRepo ? 'available' : 'misconfigured',
                'message' => $isRepo
                    ? 'Local Git publisher is ready.'
                    : 'Configured path is not a Git repository.',
                'details' => [
                    'strategy' => $this->gitSettings->strategy(),
                    'publisher' => $this->gitSettings->publisher(),
                    'repositoryConfigured' => true,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'misconfigured',
                'message' => $e->getMessage(),
                'details' => ['strategy' => $this->gitSettings->strategy()],
            ];
        }
    }
}
