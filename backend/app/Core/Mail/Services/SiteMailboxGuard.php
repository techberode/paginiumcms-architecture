<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use InvalidArgumentException;

/**
 * Site-domain mailbox lock (It.93m). No Gmail / foreign hosts.
 * Mailboxes may be @site-host or @the-same-apex (mail.example.com ↔ example.com).
 */
final class SiteMailboxGuard
{
    /** @var list<string> */
    private const PUBLIC_MAIL_APEXES = [
        'gmail.com',
        'googlemail.com',
        'outlook.com',
        'hotmail.com',
        'live.com',
        'yahoo.com',
        'icloud.com',
        'me.com',
        'aol.com',
        'proton.me',
        'protonmail.com',
        'zoho.com',
        'gmx.com',
        'gmx.net',
        'mail.com',
        'yandex.com',
        'yandex.ru',
    ];

    public static function siteHost(string ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            $host = self::usableHost(self::hostFromUrl($candidate));
            if ($host !== '') {
                return $host;
            }
        }

        return '';
    }

    public static function isMailboxAllowed(string $email, string $siteHost): bool
    {
        try {
            self::assertMailbox($email, $siteHost);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public static function assertMailbox(string $email, string $siteHost): void
    {
        $email = strtolower(trim($email));
        $siteHost = self::normalizeHost($siteHost);
        if ($siteHost === '' || self::isIpOrLocal($siteHost) || self::isPublicMailProvider($siteHost)) {
            throw new InvalidArgumentException('Site domain is not configured.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Mailbox email is invalid.');
        }
        $mailboxHost = self::normalizeHost(substr(strrchr($email, '@') ?: '', 1));
        if (self::isPublicMailProvider($mailboxHost) || !self::sameSiteFamily($mailboxHost, $siteHost)) {
            throw new InvalidArgumentException('Only mailboxes on the CMS domain are allowed.');
        }
    }

    public static function assertImapHost(string $host, string $siteHost, bool $allowPrivate): void
    {
        $host = self::normalizeHost($host);
        $siteHost = self::normalizeHost($siteHost);
        if ($host === '' || str_contains($host, '/') || str_contains($host, '@')) {
            throw new InvalidArgumentException('IMAP host is invalid.');
        }
        if (self::isPublicMailProvider($host)) {
            throw new InvalidArgumentException('IMAP host must be on the CMS domain.');
        }
        if ($allowPrivate) {
            return;
        }
        if ($siteHost === '' || self::isIpOrLocal($siteHost)) {
            throw new InvalidArgumentException('Site domain is not configured.');
        }
        if (!self::sameSiteFamily($host, $siteHost)) {
            throw new InvalidArgumentException('IMAP host must be on the CMS domain.');
        }
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP) !== false) {
            throw new InvalidArgumentException('IMAP host is not allowed.');
        }
        $ips = gethostbynamel($host);
        if ($ips === false) {
            throw new InvalidArgumentException('IMAP host could not be resolved.');
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new InvalidArgumentException('IMAP host is not allowed.');
            }
        }
    }

    public static function isSpamFolder(string $name): bool
    {
        return (bool) preg_match('/(junk|spam|bulk|nevyžiadan|nevyziadan)/i', $name);
    }

    public static function sameSiteFamily(string $host, string $siteHost): bool
    {
        $host = self::normalizeHost($host);
        $siteHost = self::normalizeHost($siteHost);
        if ($host === '' || $siteHost === '' || self::isIpOrLocal($host) || self::isIpOrLocal($siteHost)) {
            return false;
        }
        if ($host === $siteHost) {
            return true;
        }
        $hostApex = self::apexHost($host);
        $siteApex = self::apexHost($siteHost);

        return $hostApex !== '' && $hostApex === $siteApex;
    }

    private static function usableHost(string $host): string
    {
        $host = self::normalizeHost($host);
        if ($host === '' || self::isIpOrLocal($host) || self::isPublicMailProvider($host)) {
            return '';
        }

        return $host;
    }

    private static function isPublicMailProvider(string $host): bool
    {
        $apex = self::apexHost($host);

        return $apex !== '' && in_array($apex, self::PUBLIC_MAIL_APEXES, true);
    }

    private static function apexHost(string $host): string
    {
        $host = self::normalizeHost($host);
        if ($host === '' || self::isIpOrLocal($host)) {
            return '';
        }
        $parts = explode('.', $host);
        if (count($parts) < 2) {
            return '';
        }

        return $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
    }

    private static function isIpOrLocal(string $host): bool
    {
        return $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP) !== false;
    }

    private static function hostFromUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!str_contains($url, '://')) {
            $url = 'https://' . $url;
        }
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? $host : '';
    }

    private static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host, ". \t\n\r\0\x0B"));
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }
}
