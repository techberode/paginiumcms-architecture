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
}
