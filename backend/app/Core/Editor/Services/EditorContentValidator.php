<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

/**
 * Validates stored content for security threats (scripts, raw HTML in Markdown).
 *
 * Editor profiles gate toolbar/paste in the UI only — they must not block save/publish
 * for formatting the author already has in the document (e.g. fenced code blocks).
 */
final class EditorContentValidator
{
    public function __construct(
        private EditorProfileService $profiles,
        private EditorComponentRegistry $components
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function validate(string $contentType, array $payload): ?string
    {
        $content = (string) ($payload['content'] ?? '');
        if (trim($content) === '') {
            return null;
        }

        $frontMatter = is_array($payload['frontMatter'] ?? null) ? $payload['frontMatter'] : [];
        $profileId = trim((string) ($payload['editorProfile'] ?? $frontMatter['editorProfile'] ?? ''));
        if ($profileId === '') {
            $profileId = $this->profiles->resolveDefaultProfileId($contentType);
        }

        if ($profileId !== '' && $this->profiles->getProfile($profileId) === null) {
            return 'Neplatný editor profil.';
        }

        $format = (string) ($payload['contentFormat'] ?? $frontMatter['contentFormat'] ?? 'markdown');
        if (!in_array($format, ['markdown', 'html', 'tiptap_json'], true)) {
            $format = str_starts_with(trim($content), '<') ? 'html' : 'markdown';
        }

        $securityError = match ($format) {
            'html' => $this->validateHtmlSecurity($content),
            'tiptap_json' => $this->validateTiptapJsonSecurity($content),
            default => $this->validateMarkdownSecurity($content),
        };
        if ($securityError !== null) {
            return $securityError;
        }

        return match ($format) {
            'tiptap_json' => $this->validateCustomTiptapComponents($content, $profileId),
            default => $this->validateCustomMarkdownComponents($content, $profileId),
        };
    }

    private function validateCustomMarkdownComponents(string $content, string $profileId): ?string
    {
        if (!preg_match_all('/:::([a-z0-9]+(?:-[a-z0-9]+)*)/', $content, $matches)) {
            return null;
        }

        $allowed = $this->profiles->getAllowedCustomComponents($profileId);

        foreach ($matches[1] as $directive) {
            $definition = $this->components->getByMarkdownDirective($directive);
            if ($definition === null) {
                return 'Neznámy custom komponent v Markdown: ' . $directive . '.';
            }

            if (!in_array($definition->id, $allowed, true)) {
                return 'Custom komponent nie je povolený pre tento profil: ' . $definition->id . '.';
            }
        }

        return null;
    }

    private function validateCustomTiptapComponents(string $content, string $profileId): ?string
    {
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return null;
        }

        $allowed = $this->profiles->getAllowedCustomComponents($profileId);
        $customNodeTypes = $this->components->registeredTiptapNodeTypes();

        return $this->validateTiptapCustomNodes($decoded, $allowed, $customNodeTypes);
    }

    /**
     * @param array<string, mixed> $node
     * @param list<string> $allowed
     * @param list<string> $customNodeTypes
     */
    private function validateTiptapCustomNodes(array $node, array $allowed, array $customNodeTypes): ?string
    {
        $type = (string) ($node['type'] ?? '');
        if ($type !== '' && in_array($type, $customNodeTypes, true)) {
            $definition = $this->components->getByTiptapNodeType($type);
            if ($definition === null) {
                return 'Neznámy custom komponent v editore.';
            }

            if (!in_array($definition->id, $allowed, true)) {
                return 'Custom komponent nie je povolený pre tento profil: ' . $definition->id . '.';
            }
        }

        $children = $node['content'] ?? [];
        if (!is_array($children)) {
            return null;
        }

        foreach ($children as $child) {
            if (!is_array($child)) {
                continue;
            }

            $childError = $this->validateTiptapCustomNodes($child, $allowed, $customNodeTypes);
            if ($childError !== null) {
                return $childError;
            }
        }

        return null;
    }

    private function validateMarkdownSecurity(string $content): ?string
    {
        $lower = strtolower($content);

        if (str_contains($lower, '<script') || str_contains($lower, '<iframe')) {
            return 'Obsah nepovoľuje vložené skripty alebo iframe.';
        }

        if (preg_match('/<[a-z][^>]*>/i', $content) === 1) {
            return 'Markdown obsah nesmie obsahovať raw HTML tagy.';
        }

        return null;
    }

    private function validateHtmlSecurity(string $content): ?string
    {
        $lower = strtolower($content);

        if (str_contains($lower, '<iframe') || str_contains($lower, '<script')) {
            return 'Obsah nepovoľuje vložené skripty alebo iframe.';
        }

        return null;
    }

    private function validateTiptapJsonSecurity(string $content): ?string
    {
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return 'Neplatný Tiptap JSON dokument.';
        }

        if (($decoded['type'] ?? '') !== 'doc') {
            return 'Tiptap JSON musí mať koreň typu doc.';
        }

        return $this->validateTiptapNodeSecurity($decoded);
    }

    /**
     * @param array<string, mixed> $node
     */
    private function validateTiptapNodeSecurity(array $node): ?string
    {
        $type = (string) ($node['type'] ?? '');

        if ($type === 'iframe' || $type === 'script') {
            return 'Obsah nepovoľuje vložené skripty alebo iframe.';
        }

        $children = $node['content'] ?? [];
        if (!is_array($children)) {
            return null;
        }

        foreach ($children as $child) {
            if (!is_array($child)) {
                continue;
            }
            $childError = $this->validateTiptapNodeSecurity($child);
            if ($childError !== null) {
                return $childError;
            }
        }

        return null;
    }
}
