<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

/**
 * Turns IMAP RFC 2047 / HTML / quoted-printable into inbox fields (It.93m).
 * HTML is sanitized for a sandboxed iframe; text is the fallback.
 */
final class MailMimeDecoder
{
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function present(array $row, bool $withBody): array
    {
        $subject = self::header((string) ($row['subject'] ?? ''));
        $from = self::header((string) ($row['from'] ?? ''));
        $out = $row;
        $out['subject'] = $subject;
        $out['from'] = $from;
        $out['date'] = trim((string) ($row['date'] ?? ''));
        $out['seen'] = (bool) ($row['seen'] ?? false);
        $out['flagged'] = (bool) ($row['flagged'] ?? false);

        if ($withBody) {
            $source = (string) ($row['html'] ?? '');
            if ($source === '') {
                $source = (string) ($row['body'] ?? '');
            }
            $out['body'] = $source !== '' ? self::body($source) : '';
            $html = self::htmlDocument($source);
            if ($html !== '') {
                $out['html'] = $html;
            } else {
                unset($out['html']);
            }
            $out['snippet'] = self::snippet($out['body'] !== '' ? $out['body'] : $subject);
        } else {
            unset($out['body'], $out['html']);
            $snippet = self::header((string) ($row['snippet'] ?? $subject));
            $out['snippet'] = self::looksLikeHtml($snippet) ? self::snippet(self::body($snippet)) : $snippet;
        }

        return $out;
    }

    public static function header(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace("/\n[ \t]+/", ' ', $value) ?? $value;
        $value = trim($value);
        $value = preg_replace('/\?=\s+=\?/', '?==?', $value) ?? $value;

        $decoded = preg_replace_callback(
            '/=\?([^?]+)\?([BQbq])\?([^?]*)\?=/',
            static function (array $match): string {
                $charset = $match[1];
                $bytes = strtoupper($match[2]) === 'B'
                    ? (base64_decode(str_replace([' ', "\t"], '', $match[3]), true) ?: '')
                    : quoted_printable_decode(str_replace('_', ' ', $match[3]));

                return self::toUtf8($bytes, $charset);
            },
            $value
        );

        return html_entity_decode(trim($decoded ?? $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function body(string $raw): string
    {
        $raw = str_replace("\r\n", "\n", $raw);
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $plain = self::firstMimePart($raw, 'text/plain');
        if ($plain !== null) {
            return self::normalizeText(self::decodeTransfer($plain['body'], $plain['encoding']));
        }

        $html = self::firstMimePart($raw, 'text/html');
        if ($html !== null) {
            return self::normalizeText(self::htmlToText(self::decodeTransfer($html['body'], $html['encoding'])));
        }

        $decoded = self::decodeTransfer($raw, '');
        if (self::looksLikeHtml($decoded)) {
            return self::normalizeText(self::htmlToText($decoded));
        }

        return self::normalizeText($decoded);
    }

    public static function htmlDocument(string $raw): string
    {
        $raw = str_replace("\r\n", "\n", trim($raw));
        if ($raw === '') {
            return '';
        }

        $html = null;
        $part = self::firstMimePart($raw, 'text/html');
        if ($part !== null) {
            $html = self::decodeTransfer($part['body'], $part['encoding']);
        } else {
            $decoded = self::decodeTransfer($raw, '');
            if (self::looksLikeHtml($decoded)) {
                $html = $decoded;
            }
        }

        if ($html === null || trim($html) === '') {
            return '';
        }

        return MailHtmlSanitizer::document($html);
    }

    public static function snippet(string $text): string
    {
        $line = preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);
        if (function_exists('mb_substr')) {
            return mb_substr($line, 0, 160);
        }

        return substr($line, 0, 160);
    }

    public static function looksLikeHtml(string $value): bool
    {
        return (bool) preg_match('/<(?:!DOCTYPE|html|head|body|div|table|p)\b/i', $value);
    }

    /**
     * @return array{body: string, encoding: string}|null
     */
    private static function firstMimePart(string $raw, string $type): ?array
    {
        if (preg_match('/boundary=("?)([^"\s;]+)\1/i', $raw, $match) !== 1) {
            return null;
        }

        $parts = preg_split('/--' . preg_quote($match[2], '/') . '(?:--)?/', $raw);
        if ($parts === false) {
            return null;
        }

        foreach ($parts as $part) {
            $part = ltrim($part, "\n");
            if (preg_match('/Content-Type:\s*' . preg_quote($type, '/') . '/i', $part) !== 1) {
                continue;
            }
            $chunks = preg_split("/\n\n/", $part, 2);
            if ($chunks === false || !isset($chunks[1])) {
                continue;
            }
            $encoding = '';
            if (preg_match('/Content-Transfer-Encoding:\s*(\S+)/i', $chunks[0], $enc) === 1) {
                $encoding = strtolower(trim($enc[1], " \t;"));
            }

            return ['body' => rtrim($chunks[1], "-\n"), 'encoding' => $encoding];
        }

        return null;
    }

    private static function decodeTransfer(string $body, string $encoding): string
    {
        $encoding = strtolower(trim($encoding));
        $body = trim($body);
        if ($encoding === 'base64') {
            $decoded = base64_decode(preg_replace('/\s+/', '', $body) ?? $body, true);

            return is_string($decoded) ? $decoded : $body;
        }
        if ($encoding === 'quoted-printable' || $encoding === 'qp') {
            return quoted_printable_decode($body);
        }
        if ($encoding === '' && preg_match('/=(?:[0-9A-F]{2}|\r?\n)/', $body) === 1) {
            return quoted_printable_decode($body);
        }

        return $body;
    }

    private static function htmlToText(string $html): string
    {
        $html = preg_replace('#<(script|style|head)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<!--.*?-->#s', '', $html) ?? $html;
        $html = preg_replace('#<(br|br/|/p|/div|/tr|/h[1-6]|/li|/table)\s*/?>#i', "\n", $html) ?? $html;
        $html = preg_replace('#</td>#i', "\t", $html) ?? $html;
        $html = preg_replace('#<li\b[^>]*>#i', '- ', $html) ?? $html;
        $html = strip_tags($html);
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $html;
    }

    private static function normalizeText(string $text): string
    {
        $text = str_replace("\xC2\xA0", ' ', $text);
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/[ \t]{2,}/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        if (function_exists('mb_substr')) {
            $text = mb_substr($text, 0, 20000);
        } else {
            $text = substr($text, 0, 20000);
        }

        return trim($text);
    }

    private static function toUtf8(string $bytes, string $charset): string
    {
        $charset = strtoupper(trim($charset));
        if ($bytes === '' || in_array($charset, ['UTF-8', 'UTF8', 'US-ASCII', 'ASCII'], true)) {
            return $bytes;
        }
        if (function_exists('iconv')) {
            $converted = @iconv($charset, 'UTF-8//IGNORE', $bytes);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        return $bytes;
    }
}
