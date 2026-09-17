<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Services;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Http\Extensions\Capabilities\ExtensionManifestSchema;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityCatalog;
use PaginiumCMS\Support\AppVersion;
use PaginiumCMS\Support\Lang;
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

        $this->validateManifestVersion($manifest);
        $this->validateCapabilities($manifest);

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

        $this->validateEditorComponents($manifest);

        return $id;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function validateManifestVersion(array $manifest): void
    {
        $version = $manifest['manifestVersion'] ?? null;
        if (!is_int($version) || $version !== ExtensionManifestSchema::MANIFEST_VERSION) {
            throw new RuntimeException(Lang::get('manifest_version_required', [], 'extensions'));
        }
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function validateCapabilities(array $manifest): void
    {
        $raw = $manifest['capabilities'] ?? null;
        if (!is_array($raw) || array_is_list($raw) === false) {
            throw new RuntimeException(Lang::get('capabilities_required', [], 'extensions'));
        }

        $seen = [];
        foreach ($raw as $entry) {
            if (!is_string($entry)) {
                throw new RuntimeException(Lang::get('capabilities_invalid', [], 'extensions'));
            }

            $capability = trim($entry);
            if ($capability === '' || isset($seen[$capability])) {
                throw new RuntimeException(Lang::get('capabilities_invalid', [], 'extensions'));
            }

            $seen[$capability] = true;
            if (!PluginCapabilityCatalog::isAllowed($capability)) {
                throw new RuntimeException(Lang::get('capabilities_unknown', ['capability' => $capability], 'extensions'));
            }
        }
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function validateEditorComponents(array $manifest): void
    {
        $editor = $manifest['editor'] ?? null;
        if ($editor === null) {
            return;
        }

        if (!is_array($editor)) {
            throw new RuntimeException('plugin.json editor must be an object.');
        }

        $components = $editor['components'] ?? [];
        if ($components === []) {
            return;
        }

        if (!is_array($components)) {
            throw new RuntimeException('plugin.json editor.components must be an array.');
        }

        foreach ($components as $index => $entry) {
            if (!is_array($entry)) {
                throw new RuntimeException('editor.components[' . $index . '] must be an object.');
            }

            $componentId = trim((string) ($entry['id'] ?? ''));
            if ($componentId === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $componentId)) {
                throw new RuntimeException('editor.components[' . $index . '].id must be kebab-case.');
            }

            $tiptapNodeType = trim((string) ($entry['tiptapNodeType'] ?? ''));
            if ($tiptapNodeType === '' || !preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $tiptapNodeType)) {
                throw new RuntimeException('editor.components[' . $index . '].tiptapNodeType must be camelCase.');
            }

            $markdownDirective = trim((string) ($entry['markdownDirective'] ?? $componentId));
            if ($markdownDirective === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $markdownDirective)) {
                throw new RuntimeException('editor.components[' . $index . '].markdownDirective must be kebab-case.');
            }
        }
    }
}
