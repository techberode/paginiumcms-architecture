<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Playground;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Security\CspDirectiveContributorInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;

/**
 * Playground gates (It.95). Off in DEMO_MODE. CDN CSP extras only when enabled.
 */
final class PlaygroundSettings implements CspDirectiveContributorInterface
{
    public const TEMPLATES = ['react-ts', 'vanilla', 'vue'];

    public const SANDBOX_CONNECT = [
        'https://*.codesandbox.io',
        'https://codesandbox.io',
    ];

    public const SANDBOX_FRAME = "frame-src 'self' blob: https://*.codesandbox.io https://codesandbox.io";

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private DemoMode $demoMode,
    ) {
    }

    public function isDemoBlocked(): bool
    {
        return $this->demoMode->isEnabled();
    }

    public function isEnabled(): bool
    {
        if ($this->isDemoBlocked()) {
            return false;
        }

        return $this->settings->get('playground.enabled', false) === true;
    }

    public function defaultTemplate(): string
    {
        $value = (string) $this->settings->get('playground.template', 'react-ts');

        return in_array($value, self::TEMPLATES, true) ? $value : 'react-ts';
    }

    /**
     * @return list<string>
     */
    public function enabledPackIds(): array
    {
        $raw = (string) $this->settings->get('playground.enabledPacks', 'paginium-starter');
        $ids = [];
        foreach (explode(',', $raw) as $part) {
            $id = strtolower(trim($part));
            if ($id !== '' && preg_match('/^[a-z0-9][a-z0-9-]{0,62}$/', $id) === 1) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public function isPackEnabled(string $packId): bool
    {
        return in_array($packId, $this->enabledPackIds(), true);
    }

    public function gitRepoUrl(): string
    {
        return trim((string) $this->settings->get('playground.gitRepoUrl', ''));
    }

    public function gitRef(): string
    {
        $ref = trim((string) $this->settings->get('playground.gitRef', 'main'));

        return $ref !== '' ? $ref : 'main';
    }

    public function gitToken(): string
    {
        return trim((string) $this->settings->get('playground.gitToken', ''));
    }

    public function gitConfigured(): bool
    {
        return $this->gitRepoUrl() !== '';
    }

    /**
     * @return list<string>
     */
    public function extraConnectSrcTokens(): array
    {
        return $this->isEnabled() ? self::SANDBOX_CONNECT : [];
    }

    public function frameSrcDirective(): ?string
    {
        return $this->isEnabled() ? self::SANDBOX_FRAME : null;
    }
}
