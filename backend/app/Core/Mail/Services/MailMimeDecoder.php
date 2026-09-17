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
    public static function present(array $row, bool $withBody, bool $allowRemoteImages = false): array
    {
        $subject = self::header((string) ($row['subject'] ?? ''));
        $from = self::header((string) ($row['from'] ?? ''));
        $out = $row;
        $out['subject'] = $subject;
        $out['from'] = $from;
        $out['date'] = trim((string) ($row['date'] ?? ''));
        $out['seen'] = (bool) ($row['seen'] ?? false);
        $out['flagged'] = (bool) ($row['flagged'] ?? false);

        $mimeSource = is_string($row['mime'] ?? null) ? (string) $row['mime'] : '';
        if ($mimeSource !== '') {
            self::applyEnvelopeHeaders($mimeSource, $out);
        } elseif (isset($out['to']) && is_string($out['to'])) {
            $out['to'] = self::header($out['to']);
        }

        if ($withBody) {
            $source = self::resolveRawSource($row);
            $out['body'] = $source !== '' ? self::body($source) : '';
            $html = self::htmlDocument($source, $allowRemoteImages);
            if ($html !== '') {
                $out['html'] = $html;
                if (!$allowRemoteImages && str_contains($html, 'data-pg-blocked-src')) {
                    $out['remoteImagesBlocked'] = true;
                }
            } else {
                unset($out['html']);
            }
            $out['snippet'] = self::snippet($out['body'] !== '' ? $out['body'] : $subject);
        } else {
            unset($out['body'], $out['html']);
            $snippet = self::header((string) ($row['snippet'] ?? $subject));
            $out['snippet'] = self::looksLikeHtml($snippet) ? self::snippet(self::body($snippet)) : $snippet;
        }

        $out['localOnly'] = (bool) ($row['localOnly'] ?? false);

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
        $parsed = self::parseMessage($raw);
        if ($parsed['plain'] !== '') {
            return self::normalizeText($parsed['plain']);
        }
        if ($parsed['html'] !== '') {
            return self::normalizeText(self::htmlToText($parsed['html']));
        }

        return '';
    }

    public static function htmlDocument(string $raw, bool $allowRemoteImages = false): string
    {
        $parsed = self::parseMessage($raw);
        if ($parsed['html'] !== '') {
            $html = self::embedInlineImages($parsed['html'], $parsed['inline']);

            return MailHtmlSanitizer::document($html, !$allowRemoteImages);
        }
        if ($parsed['plain'] === '') {
            return '';
        }

        $plain = htmlspecialchars($parsed['plain'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return MailHtmlSanitizer::document(
            '<pre style="white-space:pre-wrap;font-family:inherit;margin:0">' . $plain . '</pre>',
            !$allowRemoteImages
        );
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
     * @param array<string, mixed> $out
     */
    private static function applyEnvelopeHeaders(string $rawMime, array &$out): void
    {
        foreach ([
            'To' => 'to',
            'Cc' => 'cc',
            'Reply-To' => 'replyTo',
        ] as $header => $key) {
            $value = self::envelopeHeaderValue($rawMime, $header);
            if ($value !== '') {
                $out[$key] = $value;
            }
        }
    }

    private static function envelopeHeaderValue(string $raw, string $name): string
    {
        $raw = str_replace("\r\n", "\n", $raw);
        $pos = strpos($raw, "\n\n");
        $headers = $pos === false ? $raw : substr($raw, 0, $pos);
        if (preg_match('/^' . preg_quote($name, '/') . ':\s*(.+)$/im', $headers, $match) !== 1) {
            return '';
        }
        $folded = preg_replace("/\n[ \t]+/", ' ', trim($match[1])) ?? trim($match[1]);

        return self::header($folded);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function resolveRawSource(array $row): string
    {
        $mime = trim((string) ($row['mime'] ?? ''));
        if ($mime !== '') {
            return $mime;
        }

        $body = (string) ($row['body'] ?? '');
        $html = (string) ($row['html'] ?? '');

        if ($body !== '' && self::containsMimeStructure($body)) {
            return $body;
        }
        if ($html !== '' && self::containsMimeStructure($html)) {
            return $html;
        }
        if ($html !== '' && self::looksLikeHtml($html) && !self::looksLikeBase64Attachment($html)) {
            return $html;
        }
        if ($body !== '' && !self::looksLikeBase64Attachment($body)) {
            return $body;
        }
        if ($body !== '') {
            return $body;
        }

        return $html;
    }

    /**
     * @return array{plain: string, html: string, inline: array<string, string>}
     */
    private static function parseMessage(string $raw): array
    {
        $raw = str_replace("\r\n", "\n", trim($raw));
        if ($raw === '') {
            return ['plain' => '', 'html' => '', 'inline' => []];
        }

        $result = ['plain' => '', 'html' => '', 'inline' => [], 'deliveryStatus' => ''];
        self::walkMime(self::stripEnvelopeHeaders($raw), $result);

        if ($result['html'] === '' && $result['plain'] === '' && $result['deliveryStatus'] === ''
            && !self::containsMimeStructure($raw)) {
            $decoded = self::decodeTransfer($raw, '');
            if (self::looksLikeHtml($decoded)) {
                $result['html'] = $decoded;
            } elseif (!self::looksLikeBase64Attachment($decoded)) {
                $result['plain'] = $decoded;
            }
        }

        return [
            'plain' => self::mergeReadablePlain($result),
            'html' => $result['html'],
            'inline' => $result['inline'],
        ];
    }

    /**
     * @param array{plain: string, html: string, inline: array<string, string>, deliveryStatus: string} $result
     */
    private static function walkMime(string $part, array &$result): void
    {
        $part = ltrim($part, "\n");
        if ($part === '') {
            return;
        }

        [$headers, $body] = self::splitHeadersBody($part);
        $contentType = self::headerField($headers, 'Content-Type');
        $encoding = strtolower(self::headerField($headers, 'Content-Transfer-Encoding'));
        $boundary = self::mimeBoundary($contentType);

        if ($boundary !== '' && str_starts_with(strtolower($contentType), 'multipart/')) {
            foreach (self::splitByBoundary($body, $boundary) as $child) {
                self::walkMime($child, $result);
            }

            return;
        }

        $decoded = self::decodeTransfer($body, $encoding);
        $type = self::mainMimeType($contentType);

        if ($type === 'text/html' || ($type === '' && self::looksLikeHtml($decoded))) {
            if ($decoded !== '') {
                $result['html'] = $decoded;
            }

            return;
        }

        if ($type === 'text/plain') {
            self::collectPlainText($decoded, $result);

            return;
        }

        if ($type === 'message/delivery-status') {
            self::collectDeliveryStatus($decoded, $result);

            return;
        }

        if (str_starts_with($type, 'image/')) {
            $cid = self::normalizeContentId(self::headerField($headers, 'Content-ID'));
            if ($cid === '' || strlen($decoded) > 2_097_152) {
                return;
            }
            $result['inline'][$cid] = 'data:' . $type . ';base64,' . base64_encode($decoded);
        }
    }

    private static function mainMimeType(string $contentType): string
    {
        $contentType = trim($contentType);
        if ($contentType === '') {
            return '';
        }
        $segments = explode(';', $contentType, 2);

        return strtolower(trim($segments[0]));
    }

    /**
     * @param array{plain: string, html: string, inline: array<string, string>, deliveryStatus: string} $result
     */
    private static function collectPlainText(string $decoded, array &$result): void
    {
        $decoded = trim($decoded);
        if ($decoded === '') {
            return;
        }
        if (self::looksLikeDeliveryStatusBlock($decoded)) {
            self::collectDeliveryStatus($decoded, $result);

            return;
        }
        if ($result['plain'] === '' || strlen($decoded) > strlen($result['plain'])) {
            $result['plain'] = $decoded;
        }
    }

    /**
     * @param array{plain: string, html: string, inline: array<string, string>, deliveryStatus: string} $result
     */
    private static function collectDeliveryStatus(string $decoded, array &$result): void
    {
        $decoded = trim($decoded);
        if ($decoded === '') {
            return;
        }
        if ($result['deliveryStatus'] === '') {
            $result['deliveryStatus'] = $decoded;

            return;
        }
        if (!str_contains($result['deliveryStatus'], $decoded)) {
            $result['deliveryStatus'] .= "\n" . $decoded;
        }
    }

    /**
     * @param array{plain: string, html: string, inline: array<string, string>, deliveryStatus: string} $result
     */
    private static function mergeReadablePlain(array $result): string
    {
        $plain = trim($result['plain']);
        $status = trim($result['deliveryStatus']);
        if ($plain !== '' && !self::looksLikeDeliveryStatusBlock($plain)) {
            if ($status !== '' && !str_contains($plain, $status)) {
                return $plain . "\n\n" . $status;
            }

            return $plain;
        }
        if ($plain !== '') {
            return $plain;
        }

        return $status;
    }

    private static function looksLikeDeliveryStatusBlock(string $text): bool
    {
        if (!str_contains($text, 'Reporting-MTA:')) {
            return false;
        }

        return !preg_match('/\b(sorry|could not|undeliver|delivery has failed|failed permanently)\b/i', $text);
    }

    private static function stripEnvelopeHeaders(string $raw): string
    {
        $trimmed = ltrim($raw);
        if (str_starts_with(strtolower($trimmed), 'content-type:')
            && !preg_match('/^(?:From|Return-Path|Received|Delivered-To):/im', $raw)) {
            return $raw;
        }
        if (!preg_match('/^(?:From|Return-Path|Received|Delivered-To):/im', $raw)) {
            return $raw;
        }
        if (preg_match('/^Content-Type:\s*(.+)$/im', $raw, $match) === 1) {
            $pos = strpos($raw, "\n\n");
            if ($pos !== false) {
                return 'Content-Type: ' . trim($match[1]) . "\n\n" . substr($raw, $pos + 2);
            }
        }
        $pos = strpos($raw, "\n\n");

        return $pos === false ? $raw : substr($raw, $pos + 2);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function splitHeadersBody(string $part): array
    {
        $chunks = preg_split("/\n\n/", ltrim($part, "\n"), 2);
        if ($chunks === false || $chunks === []) {
            return ['', ''];
        }
        if (!isset($chunks[1])) {
            return ['', $chunks[0]];
        }

        return [$chunks[0], $chunks[1]];
    }

    private static function headerField(string $headers, string $name): string
    {
        if (preg_match('/^' . preg_quote($name, '/') . ':\s*(.+)$/im', $headers, $match) !== 1) {
            return '';
        }

        return trim(preg_replace("/\n[ \t]+/", ' ', $match[1]) ?? $match[1]);
    }

    private static function mimeBoundary(string $contentType): string
    {
        if (preg_match('/boundary=("?)([^"\s;]+)\1/i', $contentType, $match) !== 1) {
            return '';
        }

        return $match[2];
    }

    /**
     * @return list<string>
     */
    private static function splitByBoundary(string $body, string $boundary): array
    {
        $parts = preg_split('/--' . preg_quote($boundary, '/') . '(?:--)?/', $body);
        if ($parts === false) {
            return [];
        }
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part, "\n-");
            if ($part !== '') {
                $out[] = $part;
            }
        }

        return $out;
    }

    private static function normalizeContentId(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, '<') && str_ends_with($value, '>')) {
            $value = substr($value, 1, -1);
        }

        return strtolower(trim($value));
    }

    /**
     * @param array<string, string> $inline
     */
    private static function embedInlineImages(string $html, array $inline): string
    {
        if ($inline === []) {
            return $html;
        }

        foreach ($inline as $cid => $dataUrl) {
            $html = str_ireplace('cid:' . $cid, $dataUrl, $html);
            $local = strstr($cid, '@', true);
            if (is_string($local) && $local !== '' && $local !== $cid) {
                $html = preg_replace(
                    '/\bcid:' . preg_quote($local, '/') . '(?![@a-z0-9._-])/i',
                    $dataUrl,
                    $html
                ) ?? $html;
            }
        }

        return $html;
    }

    private static function containsMimeStructure(string $value): bool
    {
        return str_contains($value, 'Content-Type:') && str_contains($value, 'boundary=');
    }

    private static function looksLikeBase64Attachment(string $value): bool
    {
        $compact = preg_replace('/\s+/', '', trim($value)) ?? trim($value);
        if (strlen($compact) < 120) {
            return false;
        }
        if (preg_match('/^[A-Za-z0-9+\/=]+$/', $compact) !== 1) {
            return false;
        }

        return str_starts_with($compact, 'iVBOR') || str_starts_with($compact, '/9j/') || str_starts_with($compact, 'R0lGOD');
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
