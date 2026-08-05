<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Http\Themes\Models\ThemeRecord;
use PaginiumCMS\Support\AppVersion;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Validates theme.json against the theme package contract (It.67b).
 */
final class ThemeManifestValidator
{
    /** @var list<string> */
    private const ALLOWED_SLOT_IDS = [
        'header',
        'main',
        'sidebar',
        'footer',
    ];

    /** @var list<string> */
    private const ALLOWED_SUPPORTS = [
        'appearance-tokens',
        'branding',
        'navigation',
    ];

    /**
     * @param array<string, mixed> $manifest
     */
    public function validate(array $manifest, string $folderName): string
    {
        $id = trim((string) ($manifest['id'] ?? $folderName));
        if ($id === '') {
            throw new RuntimeException('theme.json must define id.');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id)) {
            throw new RuntimeException('Theme id must be kebab-case (a-z0-9 and hyphens only).');
        }

        if (trim((string) ($manifest['name'] ?? '')) === '') {
            throw new RuntimeException('theme.json must define name.');
        }

        if (trim((string) ($manifest['version'] ?? '')) === '') {
            throw new RuntimeException('theme.json must define version.');
        }

        $minVersion = trim((string) ($manifest['minCmsVersion'] ?? ''));
        if ($minVersion !== '' && version_compare(AppVersion::current(), $minVersion, '<')) {
            throw new RuntimeException(
                sprintf('Theme requires CMS %s or newer (current %s).', $minVersion, AppVersion::current())
            );
        }

        $slots = $manifest['slots'] ?? [];
        if (!is_array($slots)) {
            throw new RuntimeException('theme.json slots must be an array.');
        }
        foreach ($slots as $slot) {
            if (!is_string($slot) || !in_array($slot, self::ALLOWED_SLOT_IDS, true)) {
                throw new RuntimeException('Unknown theme slot: ' . (is_string($slot) ? $slot : 'invalid'));
            }
        }

        $supports = $manifest['supports'] ?? [];
        if (!is_array($supports)) {
            throw new RuntimeException('theme.json supports must be an array.');
        }
        foreach ($supports as $support) {
            if (!is_string($support) || !in_array($support, self::ALLOWED_SUPPORTS, true)) {
                throw new RuntimeException('Unknown theme support flag: ' . (is_string($support) ? $support : 'invalid'));
            }
        }

        $this->rejectRemoteScriptUrls($manifest);

        return $id;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function rejectRemoteScriptUrls(array $manifest): void
    {
        $encoded = JsonHelper::encode($manifest);
        if (preg_match('/https?:\/\/[^\s"\']+\.js\b/i', $encoded) === 1) {
            throw new RuntimeException('Theme manifest must not reference remote script URLs.');
        }
    }
}
