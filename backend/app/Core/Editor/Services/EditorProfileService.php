<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use PaginiumCMS\Core\Editor\Models\EditorCapabilities;
use PaginiumCMS\Core\Editor\Models\EditorProfile;
use PaginiumCMS\Core\Editor\Services\EditorComponentRegistry;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Resolves built-in editor profiles and site defaults (Iteration 54).
 */
final class EditorProfileService
{
    /** @var array<string, EditorProfile>|null */
    private ?array $builtIn = null;

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private EditorComponentRegistry $components
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
     * @return list<array{id: string, label: string, description: string, capabilities: array{enabled: list<string>}, modes: list<string>, customComponents: list<string>}>
     */
    public function listProfilesForApi(): array
    {
        return array_map(
            static fn (EditorProfile $profile): array => $profile->toArray(),
            $this->listProfilesWithCustomComponents()
        );
    }

    /**
     * @return list<EditorProfile>
     */
    public function listProfilesWithCustomComponents(): array
    {
        return array_map(
            fn (EditorProfile $profile): EditorProfile => $this->withCustomComponents($profile),
            $this->listProfiles()
        );
    }

    public function getProfileWithCustomComponents(string $id): ?EditorProfile
    {
        $profile = $this->getProfile($id);

        return $profile !== null ? $this->withCustomComponents($profile) : null;
    }

    /**
     * @return list<string>
     */
    public function getAllowedCustomComponents(string $profileId): array
    {
        if (!(bool) $this->settings->get('editor.customComponentsEnabled', false)) {
            return [];
        }

        $map = $this->profileCustomComponentsMap();
        $configured = $map[$profileId] ?? [];

        $allowed = [];
        foreach ($configured as $componentId) {
            $componentId = trim($componentId);
            if ($componentId !== '' && $this->components->isRegistered($componentId)) {
                $allowed[] = $componentId;
            }
        }

        return array_values(array_unique($allowed));
    }

    /**
     * @return array<string, list<string>>
     */
    public function profileCustomComponentsMap(): array
    {
        $raw = (string) $this->settings->get('editor.profileCustomComponents', '{}');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $map = [];
        foreach ($decoded as $profileId => $componentIds) {
            if (!is_string($profileId) || !is_array($componentIds)) {
                continue;
            }
            $map[$profileId] = array_values(array_filter(
                array_map(static fn ($id): string => is_string($id) ? trim($id) : '', $componentIds),
                static fn (string $id): bool => $id !== ''
            ));
        }

        return $map;
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

        $profile = $this->getProfileWithCustomComponents($profileId);
        if ($profile === null) {
            $profile = $this->getProfileWithCustomComponents($this->resolveDefaultProfileId($contentType));
        }

        if ($profile === null) {
            $profile = $this->getProfileWithCustomComponents('company');
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
                    $profile->modes,
                    $profile->customComponents
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
                'Články s nadpismi, obrázkami, citáciami a ukážkami kódu — bez raw HTML a tabuliek.',
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
                    'codeBlock',
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

    private function withCustomComponents(EditorProfile $profile): EditorProfile
    {
        return new EditorProfile(
            $profile->id,
            $profile->label,
            $profile->description,
            $profile->capabilities,
            $profile->modes,
            $this->getAllowedCustomComponents($profile->id)
        );
    }
}
