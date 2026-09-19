<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;

/**
 * Normalizes and optionally probes profile social URLs (It.93o follow-up).
 */
final class SocialAccountLinkProbe
{
    private const HTTP_TIMEOUT_SECONDS = 6;

    public function __construct(
        private OutboundUrlGuard $outboundUrlGuard,
    ) {
    }

    /**
     * @return array{ok: bool, normalizedUrl: string, message: string, httpStatus: ?int}
     */
    public function verify(string $platform, string $url): array
    {
        $platform = strtolower(trim($platform));
        $normalized = self::normalizeStorageUrl($platform, $url);
        if ($normalized === '') {
            return [
                'ok' => false,
                'normalizedUrl' => '',
                'message' => 'Invalid URL or handle for this platform',
                'httpStatus' => null,
            ];
        }

        if ($platform === 'email') {
            return [
                'ok' => true,
                'normalizedUrl' => $normalized,
                'message' => 'Email address is valid',
                'httpStatus' => null,
            ];
        }

        if (in_array($platform, ['telegram', 'whatsapp', 'messenger'], true)) {
            if (!self::platformMatchesUrl($platform, $normalized)) {
                return [
                    'ok' => false,
                    'normalizedUrl' => $normalized,
                    'message' => 'URL does not match the selected platform',
                    'httpStatus' => null,
                ];
            }

            return [
                'ok' => true,
                'normalizedUrl' => $normalized,
                'message' => 'Chat link format is valid',
                'httpStatus' => null,
            ];
        }

        if (!self::platformMatchesUrl($platform, $normalized)) {
            return [
                'ok' => false,
                'normalizedUrl' => $normalized,
                'message' => 'URL does not match the selected platform',
                'httpStatus' => null,
            ];
        }

        return $this->probeHttpUrl($normalized);
    }

    public static function normalizeStorageUrl(string $platform, string $url): string
    {
        $platform = strtolower(trim($platform));
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!in_array($platform, UserProfileFields::SOCIAL_PLATFORMS, true)) {
            $platform = 'website';
        }

        if ($platform === 'email') {
            $email = preg_replace('/^mailto:/i', '', $url) ?? $url;

            return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            if (in_array($platform, ['telegram', 'whatsapp', 'messenger'], true)) {
                $chat = UserProfileFields::chatUrl($platform, $url);

                return $chat;
            }

            return '';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $url;
    }

    /**
     * @param array<string, mixed> $account
     */
    public static function isVerified(array $account): bool
    {
        return trim((string) ($account['url'] ?? '')) !== ''
            && (int) ($account['verifiedAt'] ?? 0) > 0;
    }

    private static function platformMatchesUrl(string $platform, string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return match ($platform) {
            'github' => str_contains($host, 'github.com'),
            'gitlab' => str_contains($host, 'gitlab.com'),
            'twitter' => str_contains($host, 'twitter.com') || str_contains($host, 'x.com'),
            'facebook' => str_contains($host, 'facebook.com') || str_contains($host, 'fb.com'),
            'instagram' => str_contains($host, 'instagram.com'),
            'linkedin' => str_contains($host, 'linkedin.com'),
            'youtube' => str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be'),
            'mastodon' => $host !== '' && str_contains($host, '.'),
            'discord' => str_contains($host, 'discord.com') || str_contains($host, 'discord.gg'),
            'telegram' => str_contains($host, 't.me') || str_contains($host, 'telegram.me'),
            'whatsapp' => str_contains($host, 'wa.me') || str_contains($host, 'whatsapp.com'),
            'messenger' => str_contains($host, 'm.me') || str_contains($host, 'messenger.com'),
            'website' => $host !== '',
            default => $host !== '',
        };
    }

    /**
     * @return array{ok: bool, normalizedUrl: string, message: string, httpStatus: ?int}
     */
    private function probeHttpUrl(string $url): array
    {
        if (!$this->outboundUrlGuard->isAllowed($url)) {
            return [
                'ok' => false,
                'normalizedUrl' => $url,
                'message' => 'Outbound URL is not allowed by server policy',
                'httpStatus' => null,
            ];
        }

        $status = $this->headStatusCode($url);
        if ($status === null) {
            return [
                'ok' => false,
                'normalizedUrl' => $url,
                'message' => 'Could not reach the URL (timeout or network error)',
                'httpStatus' => null,
            ];
        }

        if ($status >= 200 && $status < 400) {
            return [
                'ok' => true,
                'normalizedUrl' => $url,
                'message' => 'Link is reachable',
                'httpStatus' => $status,
            ];
        }

        if (in_array($status, [401, 403, 405, 429], true)) {
            return [
                'ok' => true,
                'normalizedUrl' => $url,
                'message' => 'Host responded (link likely valid; access may require login)',
                'httpStatus' => $status,
            ];
        }

        return [
            'ok' => false,
            'normalizedUrl' => $url,
            'message' => 'Host returned HTTP ' . $status,
            'httpStatus' => $status,
        ];
    }

    private function headStatusCode(string $url): ?int
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => self::HTTP_TIMEOUT_SECONDS,
                'ignore_errors' => true,
                'follow_location' => 1,
                'max_redirects' => 3,
                'user_agent' => 'PaginiumCMS-SocialLinkProbe/1.0',
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $headers = @get_headers($url, true, $context);
        if (!is_array($headers) || $headers === []) {
            return null;
        }

        $first = $headers[0] ?? '';
        if (!is_string($first) || !preg_match('/\s(\d{3})\s/', $first, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
