<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

/** Parse RFC5322 From into a lowercase e-mail for blocklists. */
final class MailFromAddress
{
    public static function parse(string $from): string
    {
        $from = trim($from);
        if (preg_match('/<([^>]+)>/', $from, $match) === 1) {
            return strtolower(trim($match[1]));
        }

        return strtolower($from);
    }

    public static function isPlausibleEmail(string $email): bool
    {
        return $email !== '' && str_contains($email, '@') && strlen($email) <= 320;
    }
}
