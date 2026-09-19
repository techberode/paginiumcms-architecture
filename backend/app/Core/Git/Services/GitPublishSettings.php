<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Reads effective Git publish settings from engine group (Iteration 70).
 */
final class GitPublishSettings
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    public function isEnabled(): bool
    {
        $engine = $this->settings->group('engine');

        return (bool) ($engine['gitEnabled'] ?? false);
    }

    public function strategy(): string
    {
        if (!$this->isEnabled()) {
            return 'disabled';
        }

        $engine = $this->settings->group('engine');
        $strategy = (string) ($engine['gitPublishStrategy'] ?? 'disabled');

        return in_array($strategy, ['disabled', 'immediate', 'queued'], true) ? $strategy : 'disabled';
    }

    public function isActive(): bool
    {
        return $this->strategy() !== 'disabled';
    }

    public function publisher(): string
    {
        $engine = $this->settings->group('engine');
        $publisher = (string) ($engine['gitPublisher'] ?? 'local');

        return in_array($publisher, ['local', 'github_api'], true) ? $publisher : 'local';
    }

    public function branch(): string
    {
        $engine = $this->settings->group('engine');
        $branch = trim((string) ($engine['gitBranch'] ?? 'main'));

        return $branch !== '' ? $branch : 'main';
    }

    public function githubRepository(): string
    {
        $engine = $this->settings->group('engine');

        return trim((string) ($engine['gitGithubRepository'] ?? ''));
    }

    public function githubToken(): string
    {
        $engine = $this->settings->group('engine');

        return trim((string) ($engine['gitGithubToken'] ?? ''));
    }

    /**
     * @return array{owner: string, repo: string}
     */
    public function githubOwnerRepo(): array
    {
        $raw = $this->githubRepository();
        if (preg_match('/^([A-Za-z0-9_.-]+)\/([A-Za-z0-9_.-]+)$/', $raw, $matches) !== 1) {
            throw new \RuntimeException('GitHub repository must be owner/name.');
        }

        return ['owner' => $matches[1], 'repo' => $matches[2]];
    }
}
