<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Role-gated external embed blocks in Markdown (It.91b).
 */
final class ExternalEmbedContentService
{
    public const PERMISSION_EMBED_EXTERNAL = 'content:embed-external';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private AuthorizationInterface $authorization,
        private ExternalEmbedShortcode $embedShortcode = new ExternalEmbedShortcode(),
    ) {
    }

    /**
     * @return list<string>
     */
    public function enabledProviders(): array
    {
        $editor = $this->settings->group('editor');
        $raw = trim((string) ($editor['embedProvidersEnabled'] ?? 'youtube,vimeo'));
        if ($raw === '') {
            return [];
        }

        $providers = [];
        foreach (explode(',', $raw) as $part) {
            $name = strtolower(trim($part));
            if ($name !== '' && preg_match('/^[a-z0-9-]+$/', $name) === 1) {
                $providers[] = $name;
            }
        }

        return array_values(array_unique($providers));
    }

    public function canUseExternalEmbed(?User $user): bool
    {
        if ($this->enabledProviders() === []) {
            return false;
        }

        if ($user === null) {
            return false;
        }

        return $this->authorization->hasPermission($user, self::PERMISSION_EMBED_EXTERNAL);
    }

    public function validateMarkdown(string $content, ?User $user): ?string
    {
        if (!$this->embedShortcode->containsBlock($content)) {
            return null;
        }

        if (!$this->canUseExternalEmbed($user)) {
            return 'Externé embed bloky nie sú povolené pre tento účet alebo sú vypnuté v nastaveniach.';
        }

        return $this->embedShortcode->validateBlocks($content, $this->enabledProviders());
    }
}
