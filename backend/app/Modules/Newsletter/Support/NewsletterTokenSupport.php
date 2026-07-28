<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Support;

final class NewsletterTokenSupport
{
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function verify(string $token, string $storedHash): bool
    {
        if ($token === '' || $storedHash === '') {
            return false;
        }

        return hash_equals($storedHash, self::hash($token));
    }
}
