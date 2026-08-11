<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Gdpr;

/**
 * Stable pseudonyms for GDPR anonymization (It.80e).
 */
final class GdprPseudonym
{
    public const EMAIL_DOMAIN = 'anonymized.invalid';

    public static function forSubject(string $subjectId): string
    {
        $hash = substr(hash('sha256', $subjectId . '|gdpr|paginium'), 0, 16);

        return 'anon_' . $hash;
    }

    public static function emailForSubject(string $subjectId): string
    {
        return self::forSubject($subjectId) . '@' . self::EMAIL_DOMAIN;
    }

    public static function isAnonymizedEmail(string $email): bool
    {
        $normalized = strtolower(trim($email));

        return str_ends_with($normalized, '@' . self::EMAIL_DOMAIN);
    }
}
