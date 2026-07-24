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
        private EditorProfileService $profiles
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
        if ($profileId !== '' && $this->profiles->getProfile($profileId) === null) {
            return 'Neplatný editor profil.';
        }

        $format = (string) ($payload['contentFormat'] ?? $frontMatter['contentFormat'] ?? 'markdown');
        if (!in_array($format, ['markdown', 'html', 'tiptap_json'], true)) {
            $format = str_starts_with(trim($content), '<') ? 'html' : 'markdown';
        }

        return match ($format) {
            'html' => $this->validateHtmlSecurity($content),
            'tiptap_json' => $this->validateTiptapJsonSecurity($content),
            default => $this->validateMarkdownSecurity($content),
        };
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
