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
        if (!in_array($format, ['markdown', 'html'], true)) {
            $format = str_starts_with(trim($content), '<') ? 'html' : 'markdown';
        }

        return $format === 'html'
            ? $this->validateHtml($content, $profile->capabilities)
            : $this->validateMarkdown($content, $profile->capabilities);
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
}
