<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Settings\Services;

use InvalidArgumentException;
use PaginiumCMS\Support\JsonHelper;

/**
 * Validates and normalizes marketing.socialLinksJson for admin + public footer.
 */
final class SocialLinksNormalizer
{
    public const MAX_LINKS = 12;

    /** @var list<string> */
    public const PLATFORMS = [
        'github',
        'gitlab',
        'twitter',
        'facebook',
        'instagram',
        'linkedin',
        'youtube',
        'mastodon',
        'discord',
        'website',
        'email',
        'rss',
    ];

    /**
     * @return list<array{id: string, platform: string, url: string, label: string, enabled: bool}>
     */
    public static function defaults(): array
    {
        return [
            [
                'id' => 'github-main',
                'platform' => 'github',
                'url' => 'https://github.com/techberode/paginiumcms-architecture',
                'label' => 'GitHub',
                'enabled' => true,
            ],
        ];
    }

    /**
     * @return list<array{id: string, platform: string, url: string, label: string, enabled: bool}>
     */
    public static function normalizeJson(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '[]') {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($raw);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Social links must be valid JSON array.');
        }

        if (!array_is_list($decoded)) {
            throw new InvalidArgumentException('Social links must be a JSON array.');
        }

        if (count($decoded) > self::MAX_LINKS) {
            throw new InvalidArgumentException(sprintf('Maximum %d social links allowed.', self::MAX_LINKS));
        }

        $normalized = [];
        foreach ($decoded as $index => $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException(sprintf('Social link #%d is invalid.', $index + 1));
            }

            $platform = strtolower(trim((string) ($item['platform'] ?? '')));
            if (!in_array($platform, self::PLATFORMS, true)) {
                throw new InvalidArgumentException(sprintf('Unsupported social platform: %s', $platform));
            }

            $url = trim((string) ($item['url'] ?? ''));
            if ($url === '') {
                throw new InvalidArgumentException(sprintf('Social link #%d requires a URL.', $index + 1));
            }

            if ($platform === 'email') {
                if (!filter_var($url, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException(sprintf('Social link #%d must be a valid email.', $index + 1));
                }
                $url = 'mailto:' . $url;
            } else {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    throw new InvalidArgumentException(sprintf('Social link #%d must be a valid URL.', $index + 1));
                }
                $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
                if (!in_array($scheme, ['http', 'https'], true)) {
                    throw new InvalidArgumentException(sprintf('Social link #%d must use http or https.', $index + 1));
                }
            }

            $id = trim((string) ($item['id'] ?? ''));
            if ($id === '') {
                $id = $platform . '-' . substr(md5($url), 0, 8);
            }

            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                $label = ucfirst($platform);
            }

            $normalized[] = [
                'id' => $id,
                'platform' => $platform,
                'url' => $url,
                'label' => mb_substr($label, 0, 80),
                'enabled' => (bool) ($item['enabled'] ?? true),
            ];
        }

        return $normalized;
    }

    /**
     * @return list<array{platform: string, url: string, label: string}>
     */
    public static function publicLinks(string $raw, bool $enabled): array
    {
        if (!$enabled) {
            return [];
        }

        try {
            $links = self::normalizeJson($raw);
        } catch (InvalidArgumentException) {
            return [];
        }

        $public = [];
        foreach ($links as $link) {
            if ($link['enabled'] !== true) {
                continue;
            }
            $public[] = [
                'platform' => $link['platform'],
                'url' => $link['url'],
                'label' => $link['label'],
            ];
        }

        return $public;
    }

    /**
     * @param list<array{id: string, platform: string, url: string, label: string, enabled: bool}> $links
     */
    public static function encode(array $links): string
    {
        return JsonHelper::encode($links);
    }
}
