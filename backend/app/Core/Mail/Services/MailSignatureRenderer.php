<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use InvalidArgumentException;

/**
 * Fixed HTML signature templates for outbound mail (It.93n). User content is escaped; no raw HTML overrides.
 */
final class MailSignatureRenderer
{
    /** @var list<string> */
    public const TEMPLATE_IDS = ['minimal', 'classic', 'card', 'compact', 'brand', 'support'];

    /**
     * @return list<array{id: string}>
     */
    public static function templates(): array
    {
        $out = [];
        foreach (self::TEMPLATE_IDS as $id) {
            $out[] = ['id' => $id];
        }

        return $out;
    }

    public static function isValidTemplate(string $templateId): bool
    {
        return in_array($templateId, self::TEMPLATE_IDS, true);
    }

    public static function usesAvatar(string $templateId): bool
    {
        return $templateId === 'card';
    }

    /**
     * @param array{
     *   displayName: string,
     *   jobTitle: string,
     *   phone: string,
     *   contactEmail: string,
     *   bio: string,
     *   companyName: string,
     *   website: string,
     *   avatarUrl: string
     * } $fields
     */
    public static function render(string $templateId, array $fields): string
    {
        if (!self::isValidTemplate($templateId)) {
            throw new InvalidArgumentException('Signature template is invalid.');
        }

        return match ($templateId) {
            'minimal' => self::minimal($fields),
            'classic' => self::classic($fields),
            'card' => self::card($fields),
            'compact' => self::compact($fields),
            'brand' => self::brand($fields),
            'support' => self::support($fields),
            default => self::classic($fields),
        };
    }

    /**
     * @param array{displayName: string, jobTitle: string, phone: string, contactEmail: string, bio: string, companyName: string, website: string, avatarUrl: string} $f
     */
    private static function minimal(array $f): string
    {
        $lines = array_filter([
            self::line($f['displayName'], 'font-weight:600;font-size:14px;color:#111'),
            self::join(' · ', [$f['jobTitle'], $f['companyName']]),
            self::join(' · ', [$f['contactEmail'], $f['phone']]),
        ]);

        return self::wrap(implode('', $lines));
    }

    /**
     * @param array{displayName: string, jobTitle: string, phone: string, contactEmail: string, bio: string, companyName: string, website: string, avatarUrl: string} $f
     */
    private static function classic(array $f): string
    {
        $parts = [
            self::line($f['displayName'], 'font-weight:700;font-size:15px;color:#111'),
            self::line($f['jobTitle'], 'font-size:13px;color:#444'),
            self::line($f['companyName'], 'font-size:13px;color:#444'),
            self::line($f['contactEmail'], 'font-size:13px;color:#2563eb'),
            self::line($f['phone'], 'font-size:13px;color:#444'),
            self::line($f['website'], 'font-size:12px;color:#666'),
            self::line($f['bio'], 'font-size:12px;color:#555;margin-top:6px'),
        ];

        return self::wrap(implode('', array_filter($parts)));
    }

    /**
     * @param array{displayName: string, jobTitle: string, phone: string, contactEmail: string, bio: string, companyName: string, website: string, avatarUrl: string} $f
     */
    private static function card(array $f): string
    {
        $avatarCell = $f['avatarUrl'] !== ''
            ? '<td style="vertical-align:top;padding-right:12px">'
            . '<img src="' . self::attr($f['avatarUrl']) . '" alt="" width="64" height="64" style="border-radius:50%;display:block" />'
            . '</td>'
            : '';

        $body = implode('', array_filter([
            self::line($f['displayName'], 'font-weight:700;font-size:15px;color:#111'),
            self::line($f['jobTitle'], 'font-size:13px;color:#444'),
            self::line($f['companyName'], 'font-size:13px;color:#444'),
            self::line($f['contactEmail'], 'font-size:13px;color:#2563eb'),
            self::line($f['phone'], 'font-size:13px;color:#444'),
        ]));

        $table = '<table cellpadding="0" cellspacing="0" role="presentation"><tr>'
            . $avatarCell
            . '<td style="vertical-align:top;font-family:Arial,sans-serif">' . $body . '</td></tr></table>';

        return self::wrap($table);
    }

    /**
     * @param array{displayName: string, jobTitle: string, phone: string, contactEmail: string, bio: string, companyName: string, website: string, avatarUrl: string} $f
     */
    private static function compact(array $f): string
    {
        $text = self::join(' — ', [
            $f['displayName'],
            $f['jobTitle'],
            $f['contactEmail'],
            $f['phone'],
        ]);

        return self::wrap(self::line($text, 'font-size:12px;color:#333'));
    }

    /**
     * @param array{displayName: string, jobTitle: string, phone: string, contactEmail: string, bio: string, companyName: string, website: string, avatarUrl: string} $f
     */
    private static function brand(array $f): string
    {
        $parts = [
            self::line($f['companyName'], 'font-weight:700;font-size:16px;color:#111'),
            self::line($f['displayName'], 'font-size:13px;color:#444'),
            self::line($f['jobTitle'], 'font-size:12px;color:#666'),
            self::line($f['website'], 'font-size:12px;color:#2563eb'),
            self::line($f['contactEmail'], 'font-size:12px;color:#444'),
        ];

        return self::wrap(implode('', array_filter($parts)));
    }

    /**
     * @param array{displayName: string, jobTitle: string, phone: string, contactEmail: string, bio: string, companyName: string, website: string, avatarUrl: string} $f
     */
    private static function support(array $f): string
    {
        $team = $f['companyName'] !== '' ? $f['companyName'] . ' Support' : 'Support';
        $parts = [
            self::line($team, 'font-weight:700;font-size:14px;color:#111'),
            self::line($f['displayName'], 'font-size:12px;color:#555'),
            self::line($f['contactEmail'], 'font-size:13px;color:#2563eb'),
            self::line($f['phone'], 'font-size:13px;color:#444'),
            self::line($f['bio'], 'font-size:11px;color:#666;margin-top:4px'),
        ];

        return self::wrap(implode('', array_filter($parts)));
    }

    private static function wrap(string $inner): string
    {
        if (trim(strip_tags($inner)) === '') {
            return '';
        }

        return '<div style="margin-top:16px;padding-top:12px;border-top:1px solid #e5e7eb;font-family:Arial,sans-serif">'
            . $inner
            . '</div>';
    }

    private static function line(string $text, string $style): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        return '<p style="margin:0 0 4px;' . $style . '">' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
    }

    /**
     * @param list<string> $parts
     */
    private static function join(string $sep, array $parts): string
    {
        $clean = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $clean[] = $part;
            }
        }
        if ($clean === []) {
            return '';
        }

        return '<p style="margin:0 0 4px;font-size:13px;color:#444">'
            . htmlspecialchars(implode($sep, $clean), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</p>';
    }

    private static function attr(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
