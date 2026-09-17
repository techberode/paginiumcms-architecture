<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use InvalidArgumentException;

/**
 * Parses comma/semicolon/newline-separated recipient lists for outbound mail.
 */
final class MailRecipientParser
{
    public const MAX_RECIPIENTS = 50;

    /**
     * @return list<string>
     */
    public static function parse(string $raw, int $maxRecipients = self::MAX_RECIPIENTS): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", trim($raw));
        if ($raw === '' || strpbrk($raw, "\0") !== false) {
            return [];
        }

        $segments = preg_split('/[\n,;]+/', $raw);
        if ($segments === false) {
            return [];
        }

        $out = [];
        foreach ($segments as $segment) {
            $email = self::normalizeOne($segment);
            if ($email === '') {
                continue;
            }
            if (!in_array($email, $out, true)) {
                $out[] = $email;
            }
        }

        if ($out === []) {
            return [];
        }

        if (count($out) > $maxRecipients) {
            throw new InvalidArgumentException('Too many recipients.');
        }

        return $out;
    }

    /**
     * Draft autosave: keep valid addresses, ignore incomplete segments, preserve WIP input.
     */
    public static function normalizeDraftRecipients(string $raw): string
    {
        $raw = str_replace(["\r\n", "\r"], "\n", trim($raw));
        if ($raw === '') {
            return '';
        }
        if (strpbrk($raw, "\0") !== false) {
            throw new InvalidArgumentException('Recipient is invalid.');
        }

        $segments = preg_split('/[\n,;]+/', $raw);
        if ($segments === false) {
            return $raw;
        }

        $valid = [];
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }
            try {
                $email = self::normalizeOne($segment);
            } catch (InvalidArgumentException) {
                continue;
            }
            if (!in_array($email, $valid, true)) {
                $valid[] = $email;
            }
        }

        if ($valid !== []) {
            return implode(', ', $valid);
        }

        return $raw;
    }

    /**
     * @param list<string> $recipients
     */
    public static function formatToHeader(array $recipients): string
    {
        $parts = [];
        foreach ($recipients as $email) {
            $parts[] = '<' . $email . '>';
        }

        return implode(', ', $parts);
    }

    private static function normalizeOne(string $segment): string
    {
        $segment = trim($segment);
        if ($segment === '' || strpbrk($segment, "\r\n") !== false) {
            return '';
        }

        if (preg_match('/<([^>]+)>/', $segment, $match) === 1) {
            $segment = trim($match[1]);
        }

        $segment = trim($segment);
        if ($segment === '' || filter_var($segment, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Recipient is invalid.');
        }

        return $segment;
    }
}
