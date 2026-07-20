<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use PaginiumCMS\Core\Editor\Models\EditorCapabilities;
use PaginiumCMS\Core\Editor\Models\EditorProfile;

/**
 * Validates stored content against an editor profile whitelist (Iteration 54).
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

        $profile = $this->profiles->resolveForContent($contentType, array_merge($frontMatter, [
            'editorProfile' => $profileId !== '' ? $profileId : ($frontMatter['editorProfile'] ?? ''),
            'editorCapabilities' => $payload['editorCapabilities'] ?? $frontMatter['editorCapabilities'] ?? null,
        ]));

        $format = (string) ($payload['contentFormat'] ?? $frontMatter['contentFormat'] ?? 'markdown');
        if (!in_array($format, ['markdown', 'html', 'tiptap_json'], true)) {
            $format = str_starts_with(trim($content), '<') ? 'html' : 'markdown';
        }

        return match ($format) {
            'html' => $this->validateHtml($content, $profile->capabilities),
            'tiptap_json' => $this->validateTiptapJson($content, $profile->capabilities),
            default => $this->validateMarkdown($content, $profile->capabilities),
        };
    }

    private function validateMarkdown(string $content, EditorCapabilities $caps): ?string
    {
        if (!$caps->allows('image') && preg_match('/!\[[^\]]*\]\([^)]+\)/', $content) === 1) {
            return 'Profil editora nepovoľuje obrázky.';
        }

        if (!$caps->allows('codeBlock') && preg_match('/```/', $content) === 1) {
            return 'Profil editora nepovoľuje bloky kódu.';
        }

        if (!$caps->allows('code') && preg_match('/(?<!`)`[^`\n]+`(?!`)/', $content) === 1) {
            return 'Profil editora nepovoľuje inline kód.';
        }

        if (!$caps->allows('link') && preg_match('/\[[^\]]+\]\([^)]+\)/', $content) === 1) {
            return 'Profil editora nepovoľuje odkazy.';
        }

        if (!$caps->allows('heading') && preg_match('/^#{1,6}\s+/m', $content) === 1) {
            return 'Profil editora nepovoľuje nadpisy.';
        }

        if (!$caps->allows('bulletList') && preg_match('/^\s*[-*+]\s+/m', $content) === 1) {
            return 'Profil editora nepovoľuje odrážkové zoznamy.';
        }

        if (!$caps->allows('orderedList') && preg_match('/^\s*\d+\.\s+/m', $content) === 1) {
            return 'Profil editora nepovoľuje číslované zoznamy.';
        }

        if (!$caps->allows('blockquote') && preg_match('/^\s*>/m', $content) === 1) {
            return 'Profil editora nepovoľuje citácie.';
        }

        if (!$caps->allows('table') && preg_match('/^\s*\|.+\|\s*$/m', $content) === 1) {
            return 'Profil editora nepovoľuje tabuľky.';
        }

        if (preg_match('/<[a-z][^>]*>/i', $content) === 1) {
            return 'Profil editora nepovoľuje raw HTML v Markdown obsahu.';
        }

        return null;
    }

    private function validateHtml(string $content, EditorCapabilities $caps): ?string
    {
        $lower = strtolower($content);

        if (!$caps->allows('image') && str_contains($lower, '<img')) {
            return 'Profil editora nepovoľuje obrázky.';
        }

        if (!$caps->allows('table') && str_contains($lower, '<table')) {
            return 'Profil editora nepovoľuje tabuľky.';
        }

        if (!$caps->allows('codeBlock') && (str_contains($lower, '<pre') || str_contains($lower, '<code'))) {
            return 'Profil editora nepovoľuje bloky kódu.';
        }

        if (!$caps->allows('link') && str_contains($lower, '<a ')) {
            return 'Profil editora nepovoľuje odkazy.';
        }

        if (!$caps->allows('heading') && preg_match('/<h[1-6][\s>]/i', $content) === 1) {
            return 'Profil editora nepovoľuje nadpisy.';
        }

        if (!$caps->allows('bulletList') && str_contains($lower, '<ul')) {
            return 'Profil editora nepovoľuje odrážkové zoznamy.';
        }

        if (!$caps->allows('orderedList') && str_contains($lower, '<ol')) {
            return 'Profil editora nepovoľuje číslované zoznamy.';
        }

        if (!$caps->allows('blockquote') && str_contains($lower, '<blockquote')) {
            return 'Profil editora nepovoľuje citácie.';
        }

        if (!$caps->allows('horizontalRule') && str_contains($lower, '<hr')) {
            return 'Profil editora nepovoľuje horizontálne čiary.';
        }

        if (!$caps->allows('underline') && str_contains($lower, '<u')) {
            return 'Profil editora nepovoľuje podčiarknutie.';
        }

        if (!$caps->allows('strike') && (str_contains($lower, '<s') || str_contains($lower, '<strike'))) {
            return 'Profil editora nepovoľuje prečiarknutie.';
        }

        if (!$caps->allows('color') && str_contains($lower, 'style=')) {
            return 'Profil editora nepovoľuje farby textu.';
        }

        if (str_contains($lower, '<iframe') || str_contains($lower, '<script')) {
            return 'Profil editora nepovoľuje vložené skripty alebo iframe.';
        }

        return null;
    }

    private function validateTiptapJson(string $content, EditorCapabilities $caps): ?string
    {
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return 'Neplatný Tiptap JSON dokument.';
        }

        if (($decoded['type'] ?? '') !== 'doc') {
            return 'Tiptap JSON musí mať koreň typu doc.';
        }

        return $this->validateTiptapNode($decoded, $caps);
    }

    /**
     * @param array<string, mixed> $node
     */
    private function validateTiptapNode(array $node, EditorCapabilities $caps): ?string
    {
        $type = (string) ($node['type'] ?? '');

        $error = match ($type) {
            'image' => $caps->allows('image') ? null : 'Profil editora nepovoľuje obrázky.',
            'table', 'tableRow', 'tableHeader', 'tableCell' => $caps->allows('table') ? null : 'Profil editora nepovoľuje tabuľky.',
            'codeBlock' => $caps->allows('codeBlock') ? null : 'Profil editora nepovoľuje bloky kódu.',
            'heading' => $caps->allows('heading') ? null : 'Profil editora nepovoľuje nadpisy.',
            'bulletList', 'listItem' => $caps->allows('bulletList') ? null : 'Profil editora nepovoľuje odrážkové zoznamy.',
            'orderedList' => $caps->allows('orderedList') ? null : 'Profil editora nepovoľuje číslované zoznamy.',
            'blockquote' => $caps->allows('blockquote') ? null : 'Profil editora nepovoľuje citácie.',
            'horizontalRule' => $caps->allows('horizontalRule') ? null : 'Profil editora nepovoľuje horizontálne čiary.',
            default => null,
        };

        if ($error !== null) {
            return $error;
        }

        if ($type === 'text' && is_array($node['marks'] ?? null)) {
            foreach ($node['marks'] as $mark) {
                if (!is_array($mark)) {
                    continue;
                }
                $markType = (string) ($mark['type'] ?? '');
                if ($markType === 'link' && !$caps->allows('link')) {
                    return 'Profil editora nepovoľuje odkazy.';
                }
                if ($markType === 'code' && !$caps->allows('code')) {
                    return 'Profil editora nepovoľuje inline kód.';
                }
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
            $childError = $this->validateTiptapNode($child, $caps);
            if ($childError !== null) {
                return $childError;
            }
        }

        return null;
    }
}
