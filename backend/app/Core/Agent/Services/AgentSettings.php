<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Typed reader for Settings → agent (It.75). Defaults are fail-closed.
 */
final class AgentSettings
{
    public const KNOWN_TOOLS = [
        'content.read',
        'content.propose_patch',
        'seo.suggest_meta',
        'media.suggest_alt',
        'comments.summarize',
        'translation.translate',
    ];

    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function group(): array
    {
        return $this->settings->group('agent');
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->group()['enabled'] ?? false);
    }

    public function provider(): string
    {
        $provider = trim((string) ($this->group()['provider'] ?? 'none'));

        return in_array($provider, ['none', 'ollama', 'openai_compatible'], true) ? $provider : 'none';
    }

    public function isActive(): bool
    {
        return $this->isEnabled() && $this->provider() !== 'none';
    }

    public function baseUrl(): string
    {
        return trim((string) ($this->group()['baseUrl'] ?? ''));
    }

    public function apiKey(): string
    {
        $key = trim((string) ($this->group()['apiKey'] ?? ''));

        return $key === '********' ? '' : $key;
    }

    public function model(): string
    {
        return trim((string) ($this->group()['model'] ?? ''));
    }

    public function maxTokensPerRun(): int
    {
        return max(256, min(32000, (int) ($this->group()['maxTokensPerRun'] ?? 4000)));
    }

    public function maxToolSteps(): int
    {
        return max(1, min(12, (int) ($this->group()['maxToolSteps'] ?? 6)));
    }

    public function dailyTokenLimit(): int
    {
        return max(0, (int) ($this->group()['dailyTokenLimit'] ?? 0));
    }

    public function proposalTtlSeconds(): int
    {
        $minutes = max(5, min(1440, (int) ($this->group()['proposalTtlMinutes'] ?? 60)));

        return $minutes * 60;
    }

    public function timeoutSeconds(): int
    {
        return max(5, min(120, (int) ($this->group()['timeoutSeconds'] ?? 30)));
    }

    /**
     * @return list<string>
     */
    public function allowedTools(): array
    {
        $raw = trim((string) ($this->group()['allowedTools'] ?? ''));
        if ($raw === '') {
            return [];
        }

        $names = [];
        foreach (explode(',', $raw) as $part) {
            $name = strtolower(trim($part));
            if (in_array($name, self::KNOWN_TOOLS, true) && !in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
