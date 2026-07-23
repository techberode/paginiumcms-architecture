<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Services;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Support\AppVersion;
use RuntimeException;

/**
 * Validates plugin.json against extension code policy (Wave 5d).
 */
final class ExtensionManifestValidator
{

    /**
     * @param array<string, mixed> $manifest
     */
    public function validate(array $manifest, string $folderName): string
    {
        $id = trim((string) ($manifest['id'] ?? $folderName));
        if ($id === '') {
            throw new RuntimeException('plugin.json must define id.');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id)) {
            throw new RuntimeException('Extension id must be kebab-case (a-z0-9 and hyphens only).');
        }

        if (trim((string) ($manifest['name'] ?? '')) === '') {
            throw new RuntimeException('plugin.json must define name.');
        }

        if (trim((string) ($manifest['version'] ?? '')) === '') {
            throw new RuntimeException('plugin.json must define version.');
        }

        $minVersion = trim((string) ($manifest['minCmsVersion'] ?? ''));
        if ($minVersion !== '' && version_compare(AppVersion::current(), $minVersion, '<')) {
            throw new RuntimeException(
                sprintf('Extension requires CMS %s or newer (current %s).', $minVersion, AppVersion::current())
            );
        }

        $hooks = $manifest['hooks'] ?? [];
        if (!is_array($hooks)) {
            throw new RuntimeException('plugin.json hooks must be an object map.');
        }

        foreach (array_keys($hooks) as $hookName) {
            if (!is_string($hookName) || !HookCatalog::isRegistered($hookName)) {
                throw new RuntimeException(
                    'Unknown hook in manifest: ' . (is_string($hookName) ? $hookName : 'invalid')
                    . '. Allowed: ' . implode(', ', HookCatalog::all())
                );
            }
        }

        return $id;
    }
}
