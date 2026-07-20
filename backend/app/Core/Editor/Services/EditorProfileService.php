<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use PaginiumCMS\Core\Editor\Models\EditorCapabilities;
use PaginiumCMS\Core\Editor\Models\EditorProfile;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Resolves built-in editor profiles and site defaults (Iteration 54).
 */
final class EditorProfileService
{
    /** @var array<string, EditorProfile>|null */
    private ?array $builtIn = null;

    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * @return list<EditorProfile>
     */
    public function listProfiles(): array
    {
        return array_values($this->builtInProfiles());
    }

    /**
     * @return list<array{id: string, label: string, description: string, capabilities: array{enabled: list<string>}, modes: list<string>}>
     */
    public function listProfilesForApi(): array
    {
        return array_map(
            static fn (EditorProfile $profile): array => $profile->toArray(),
            $this->listProfiles()
        );
    }

    public function getProfile(string $id): ?EditorProfile
    {
        return $this->builtInProfiles()[$id] ?? null;
    }

    public function resolveDefaultProfileId(string $contentType): string
    {
        $key = $contentType === 'article' ? 'defaultProfileArticle' : 'defaultProfilePage';
        $configured = (string) $this->settings->get('editor.' . $key, '');
        if ($configured !== '' && $this->getProfile($configured) !== null) {
            return $configured;
        }

        return $contentType === 'article' ? 'blog' : 'company';
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    public function resolveForContent(string $contentType, array $frontMatter): EditorProfile
    {
        $profileId = trim((string) ($frontMatter['editorProfile'] ?? ''));
        if ($profileId === '') {
            $profileId = $this->resolveDefaultProfileId($contentType);
        }

        $profile = $this->getProfile($profileId);
        if ($profile === null) {
            $profile = $this->getProfile($this->resolveDefaultProfileId($contentType));
        }

        if ($profile === null) {
            $profile = $this->getProfile('company');
        }

        /** @var EditorProfile $profile */
        $override = $frontMatter['editorCapabilities'] ?? null;
        if (is_array($override)) {
            $merged = EditorCapabilities::fromArray($override);
            if ($merged->enabled !== []) {
                return new EditorProfile(
                    $profile->id,
                    $profile->label,
                    $profile->description,
                    $merged,
                    $profile->modes
                );
            }
        }

        return $profile;
    }

    /**
     * @return array<string, EditorProfile>
     */
    private function builtInProfiles(): array
    {
        if ($this->builtIn !== null) {
            return $this->builtIn;
        }

        $this->builtIn = [
            'company' => new EditorProfile(
                'company',
                'Firemná stránka',
                'Základné formátovanie pre firemné texty — bez médií a pokročilých blokov.',
                new EditorCapabilities(['bold', 'italic', 'heading', 'bulletList', 'orderedList', 'link'])
            ),
            'blog' => new EditorProfile(
                'blog',
                'Blog',
                'Články s nadpismi, obrázkami a citáciami — bez raw HTML a tabuliek.',
                new EditorCapabilities([
                    'bold',
                    'italic',
                    'heading',
                    'bulletList',
                    'orderedList',
                    'blockquote',
                    'link',
                    'image',
                    'code',
                ])
            ),
            'minimal' => new EditorProfile(
                'minimal',
                'Minimálny',
                'Len základné formátovanie — vhodné pre právne texty.',
                new EditorCapabilities(['bold', 'italic', 'link'])
            ),
            'developer' => new EditorProfile(
                'developer',
                'Developer',
                'Plný editor vrátane kódu a tabuliek.',
                new EditorCapabilities(EditorCapabilities::ALL)
            ),
        ];

        return $this->builtIn;
    }
}
