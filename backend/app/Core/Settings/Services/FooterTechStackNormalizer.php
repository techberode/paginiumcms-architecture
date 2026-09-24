<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Settings\Services;

use InvalidArgumentException;
use PaginiumCMS\Support\JsonHelper;

/**
 * Validates marketing.footerTechStackJson for the public footer tech watermark.
 */
final class FooterTechStackNormalizer
{
    public const MAX_ITEMS = 16;

    /** @var list<string> */
    public const ICON_IDS = [
        'php',
        'react',
        'vite',
        'typescript',
        'docker',
        'tailwind',
        'node',
        'mysql',
        'linux',
        'git',
    ];

    /**
     * @return list<array{id: string, label: string, url: string, icon: string, enabled: bool}>
     */
    public static function defaults(): array
    {
        return [
            [
                'id' => 'php',
                'label' => 'PHP 8.5',
                'url' => 'https://www.php.net/',
                'icon' => 'php',
                'enabled' => true,
            ],
            [
                'id' => 'react',
                'label' => 'React',
                'url' => 'https://react.dev/',
                'icon' => 'react',
                'enabled' => true,
            ],
            [
                'id' => 'vite',
                'label' => 'Vite',
                'url' => 'https://vite.dev/',
                'icon' => 'vite',
                'enabled' => true,
            ],
        ];
    }

    /**
     * @return list<array{id: string, label: string, url: string, icon: string, enabled: bool}>
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
            throw new InvalidArgumentException('Footer tech stack must be valid JSON array.');
        }

        if (!array_is_list($decoded)) {
            throw new InvalidArgumentException('Footer tech stack must be a JSON array.');
        }

        if (count($decoded) > self::MAX_ITEMS) {
            throw new InvalidArgumentException(sprintf('Maximum %d tech stack items allowed.', self::MAX_ITEMS));
        }

        $normalized = [];
        foreach ($decoded as $index => $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException(sprintf('Tech stack item #%d is invalid.', $index + 1));
            }

            $id = trim((string) ($item['id'] ?? ''));
            if ($id === '' || strlen($id) > 40) {
                throw new InvalidArgumentException(sprintf('Tech stack item #%d needs a valid id.', $index + 1));
            }

            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '' || strlen($label) > 80) {
                throw new InvalidArgumentException(sprintf('Tech stack item #%d needs a label.', $index + 1));
            }

            $url = trim((string) ($item['url'] ?? ''));
            if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException(sprintf('Tech stack item #%d has an invalid URL.', $index + 1));
            }

            $icon = strtolower(trim((string) ($item['icon'] ?? $id)));
            if ($icon === '' || !in_array($icon, self::ICON_IDS, true)) {
                $icon = in_array($id, self::ICON_IDS, true) ? $id : 'react';
            }

            $normalized[] = [
                'id' => $id,
                'label' => $label,
                'url' => $url,
                'icon' => $icon,
                'enabled' => (bool) ($item['enabled'] ?? true),
            ];
        }

        return $normalized;
    }

    /**
     * @param list<array{id: string, label: string, url: string, icon: string, enabled: bool}> $items
     */
    public static function encode(array $items): string
    {
        return JsonHelper::encode($items);
    }

    /**
     * @return list<array{id: string, label: string, url: string, icon: string}>
     */
    public static function publicItems(string $raw, bool $masterEnabled): array
    {
        if (!$masterEnabled) {
            return [];
        }

        $items = self::normalizeJson($raw);
        if ($items === []) {
            $items = self::defaults();
        }

        $public = [];
        foreach ($items as $item) {
            if (!$item['enabled']) {
                continue;
            }
            $public[] = [
                'id' => $item['id'],
                'label' => $item['label'],
                'url' => $item['url'],
                'icon' => $item['icon'],
            ];
        }

        return $public;
    }
}
