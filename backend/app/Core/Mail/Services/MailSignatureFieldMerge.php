<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use PaginiumCMS\Core\Media\Services\DamMediaUrl;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Merges CMS user profile, company settings, active mailbox, and per-mailbox overrides (It.93n).
 */
final class MailSignatureFieldMerge
{
    public const MAX_DISPLAY_NAME = 120;

    public const MAX_JOB_TITLE = 80;

    public const MAX_PHONE = 40;

    public const MAX_BIO = 500;

    public const MAX_COMPANY = 200;

    public const MAX_WEBSITE = 500;

    /** @var list<string> */
    public const OVERRIDE_KEYS = [
        'displayName',
        'jobTitle',
        'phone',
        'contactEmail',
        'bio',
        'companyName',
        'website',
        'avatarUrl',
    ];

    /**
     * @param array<string, mixed> $companySettings
     * @param array<string, mixed> $overrides
     * @return array{
     *   displayName: string,
     *   jobTitle: string,
     *   phone: string,
     *   contactEmail: string,
     *   bio: string,
     *   companyName: string,
     *   website: string,
     *   avatarUrl: string
     * }
     */
    public static function resolve(User $user, string $activeMailbox, array $companySettings, array $overrides, string $siteUrl): array
    {
        $base = [
            'displayName' => trim($user->getName()),
            'jobTitle' => trim($user->getJobTitle()),
            'phone' => trim($user->getPhone()),
            'contactEmail' => strtolower(trim($activeMailbox)),
            'bio' => trim($user->getBio()),
            'companyName' => trim((string) ($companySettings['name'] ?? '')),
            'website' => trim((string) ($companySettings['website'] ?? '')),
            'avatarUrl' => self::absoluteMediaUrl(trim((string) ($user->getAvatarUrl() ?? '')), $siteUrl),
        ];

        if ($base['displayName'] === '') {
            $base['displayName'] = $base['contactEmail'];
        }

        foreach (self::OVERRIDE_KEYS as $key) {
            if (!array_key_exists($key, $overrides)) {
                continue;
            }
            $value = trim((string) $overrides[$key]);
            if ($value === '') {
                continue;
            }
            $base[$key] = self::sanitizeField($key, $value, $siteUrl);
        }

        return $base;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    public static function normalizeOverrides(array $payload, string $siteUrl = ''): array
    {
        $out = [];
        foreach (self::OVERRIDE_KEYS as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = trim((string) $payload[$key]);
            if ($value === '') {
                continue;
            }
            $out[$key] = self::sanitizeField($key, $value, $siteUrl);
        }

        return $out;
    }

    private static function sanitizeField(string $key, string $value, string $siteUrl): string
    {
        return match ($key) {
            'displayName' => LogSanitizer::value($value, self::MAX_DISPLAY_NAME),
            'jobTitle' => LogSanitizer::value($value, self::MAX_JOB_TITLE),
            'phone' => LogSanitizer::value($value, self::MAX_PHONE),
            'contactEmail' => self::normalizeEmail($value),
            'bio' => LogSanitizer::value($value, self::MAX_BIO),
            'companyName' => LogSanitizer::value($value, self::MAX_COMPANY),
            'website' => LogSanitizer::value($value, self::MAX_WEBSITE),
            'avatarUrl' => self::absoluteMediaUrl(DamMediaUrl::sanitize($value), $siteUrl),
            default => LogSanitizer::value($value, 255),
        };
    }

    private static function normalizeEmail(string $value): string
    {
        $value = strtolower(LogSanitizer::value(trim($value), 255));
        if ($value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return '';
        }

        return $value;
    }

    private static function absoluteMediaUrl(string $url, string $siteUrl): string
    {
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return DamMediaUrl::sanitize($url) !== '' ? $url : '';
        }
        if (!str_starts_with($url, '/')) {
            return '';
        }
        $siteUrl = rtrim(trim($siteUrl), '/');
        if ($siteUrl === '') {
            return DamMediaUrl::sanitize($url) !== '' ? $url : '';
        }

        return $siteUrl . $url;
    }
}
