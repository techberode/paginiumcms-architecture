<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

/**
 * Naming contract for PHPUnit/integration artefacts — only these may be purged by dev hygiene.
 *
 * Manual dev QA should use {@see self::uniqueSlug()} (prefix `qa-`) so cleanup never touches real content.
 */
final class TestArtifactNaming
{
    public const QA_PREFIX = 'qa-';

    /**
     * Known PHPUnit slug prefixes (legacy + qa-). Real pages/articles must NOT match these.
     *
     * @var non-empty-string
     */
    public const CONTENT_SLUG_PATTERN = '/^(?:'
        . 'qa-'
        . '|bulk-[ab]-'
        . '|bulk-trash-'
        . '|purge-trash-'
        . '|trash-test-'
        . '|seo-test-'
        . '|seo-draft-'
        . '|seo-cache-collision-'
        . '|seo-draft-page-test'
        . '|editor-page-'
        . '|otp-page-'
        . '|forbidden-'
        . '|profile-test-'
        . '|security-test-'
        . '|scheduled-test-'
        . '|scheduled-save-'
        . '|scheduled-hidden-'
        . '|scheduled-page-'
        . '|future-page-'
        . '|otp-blocked-'
        . '|locale-create-'
        . '|locale-merge-'
        . '|locale-invalid-'
        . '|locale-patch-'
        . '|dup-source-'
        . '|bulk-tags-[ab]-'
        . '|calendar-test-'
        . '|headless-'
        . '|acl-open-'
        . '|acl-restricted-'
        . '|acl-hidden-'
        . '|acl-list-hidden-'
        . '|acl-super-'
        . '|acl-draft-'
        . '|acl-admin-ok-'
        . '|nonexistent-slug-'
        . '|nonexistent-'
        . '|test-article-'
        . '|blocked-guest-'
        . '|otp-comment-'
        . '|otp-parsed-body-'
        . '|hp-comment-'
        . '|spam-comment-'
        . '|gdpr-export-'
        . '|gdpr-anon-'
        . '|dedupe-test'
        . '|bulk-status'
        . ')/';

    /**
     * @var non-empty-string
     */
    public const MEDIA_FILE_PATTERN = '/^(?:'
        . 'qa-'
        . '|test-upload'
        . '|private-upload'
        . '|hardening-test'
        . '|folder-meta-test'
        . '|campaign-test'
        . '|sample\.'
        . ')/i';

    public static function uniqueSlug(string $label = 'page'): string
    {
        $normalized = strtolower(trim($label));
        $normalized = preg_replace('/[^a-z0-9-]+/', '-', $normalized) ?? 'page';
        $normalized = trim($normalized, '-') ?: 'page';

        return self::QA_PREFIX . $normalized . '-' . uniqid('', true);
    }

    public static function isTestContentSlug(string $slug): bool
    {
        return preg_match(self::CONTENT_SLUG_PATTERN, $slug) === 1;
    }

    public static function isTestMediaFileName(string $fileName): bool
    {
        return preg_match(self::MEDIA_FILE_PATTERN, $fileName) === 1;
    }

    public static function slugFromBasename(string $basename): string
    {
        $slug = preg_replace('/\.(md|json)$/i', '', $basename) ?? $basename;

        return $slug;
    }

    public static function isTestContentReference(string $reference): bool
    {
        if (self::isTestContentSlug($reference)) {
            return true;
        }

        return self::isTestContentSlug(self::slugFromBasename(basename(str_replace('\\', '/', $reference))));
    }
}
