<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

/**
 * Builds RFC 5322 messages for SMTP send and IMAP APPEND (same bytes).
 */
final class MailOutboundMimeBuilder
{
    /**
     * @param list<string> $recipients
     * @param list<array{contentId: string, mime: string, bytes: string}> $inlineImages
     */
    public static function buildRfc822(
        string $fromEmail,
        string $fromName,
        array $recipients,
        string $subject,
        string $htmlBody,
        array $inlineImages = [],
        ?string $date = null,
    ): string {
        if ($recipients === []) {
            throw new \InvalidArgumentException('At least one recipient is required.');
        }

        $body = self::buildMimeBody($htmlBody, $inlineImages);
        $headers = [
            'Date: ' . ($date ?? gmdate('D, d M Y H:i:s') . ' +0000'),
            'From: ' . self::formatAddress($fromEmail, $fromName),
            'To: ' . MailRecipientParser::formatToHeader($recipients),
            'Subject: ' . self::encodeHeader($subject),
            'MIME-Version: 1.0',
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@paginiumcms.local>',
        ];
        foreach ($body['headers'] as $headerLine) {
            $headers[] = $headerLine;
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $body['content'];
    }

    /**
     * @param list<array{contentId: string, mime: string, bytes: string}> $inlineImages
     * @return array{headers: list<string>, content: string}
     */
    public static function buildMimeBody(string $htmlBody, array $inlineImages): array
    {
        if ($inlineImages === []) {
            return [
                'headers' => [
                    'Content-Type: text/html; charset=UTF-8',
                    'Content-Transfer-Encoding: 8bit',
                ],
                'content' => $htmlBody,
            ];
        }

        $related = 'rel_' . bin2hex(random_bytes(8));
        $headers = [
            'Content-Type: multipart/related; boundary="' . $related . '"',
        ];
        $parts = [];
        $parts[] = '--' . $related;
        $parts[] = 'Content-Type: text/html; charset=UTF-8';
        $parts[] = 'Content-Transfer-Encoding: 8bit';
        $parts[] = '';
        $parts[] = $htmlBody;

        foreach ($inlineImages as $image) {
            $contentId = trim($image['contentId']);
            $mime = trim($image['mime']);
            $bytes = $image['bytes'];
            if ($contentId === '' || $mime === '' || $bytes === '') {
                continue;
            }
            $parts[] = '--' . $related;
            $parts[] = 'Content-Type: ' . $mime;
            $parts[] = 'Content-Transfer-Encoding: base64';
            $parts[] = 'Content-ID: <' . $contentId . '>';
            $parts[] = 'Content-Disposition: inline; filename="avatar"';
            $parts[] = '';
            $parts[] = chunk_split(base64_encode($bytes), 76, "\r\n");
        }

        $parts[] = '--' . $related . '--';

        return [
            'headers' => $headers,
            'content' => rtrim(implode("\r\n", $parts)),
        ];
    }

    private static function formatAddress(string $email, string $name): string
    {
        if ($name === '') {
            return '<' . $email . '>';
        }

        return self::encodeHeader($name) . ' <' . $email . '>';
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
