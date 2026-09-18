<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Services;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityCatalog;
use PaginiumCMS\Support\AppVersion;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Writes a capability-compliant plugin skeleton under Http/Extensions/{id}/ (It.89e).
 * Does not enable the plugin or touch data/plugins.json.
 */
final class PluginScaffoldService
{
    public function __construct(private string $extensionsRoot)
    {
        $this->extensionsRoot = rtrim($extensionsRoot, '/');
    }

    /**
     * @param list<string> $capabilities
     */
    public function create(string $id, string $name, array $capabilities): string
    {
        $id = trim($id);
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id)) {
            throw new RuntimeException('Extension id must be kebab-case (a-z0-9 and hyphens only).');
        }

        $name = trim($name);
        if ($name === '') {
            $name = $this->titleFromId($id);
        }

        $caps = $this->normalizeCapabilities($capabilities);
        if ($caps === []) {
            $caps = [PluginCapabilityCatalog::CONTENT_READ];
        }

        foreach ($caps as $capability) {
            if (!PluginCapabilityCatalog::isAllowed($capability)) {
                throw new RuntimeException('Unknown plugin capability: ' . $capability);
            }
        }

        $root = $this->extensionsRoot . '/' . $id;
        if (is_dir($root) || is_file($root)) {
            throw new RuntimeException('Extension already exists: ' . $id);
        }

        $ns = $this->namespaceFromId($id);
        $srcDir = $root . '/src';
        if (!mkdir($srcDir, 0775, true) && !is_dir($srcDir)) {
            throw new RuntimeException('Unable to create plugin directory: ' . $root);
        }

        $manifest = [
            'id' => $id,
            'name' => $name,
            'version' => '0.1.0',
            'manifestVersion' => 1,
            'capabilities' => $caps,
            'description' => 'Scaffolded plugin (It.89e). Enable from Extensions after review.',
            'author' => '',
            'minCmsVersion' => AppVersion::current(),
            'hooks' => [
                HookCatalog::EXTENSION_BOOT => $ns . '\\Hooks::onBoot',
            ],
            'routes' => false,
            'frontend' => false,
        ];

        $ok = @file_put_contents(
            $root . '/plugin.json',
            JsonHelper::encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        if ($ok === false) {
            $this->removeDir($root);
            throw new RuntimeException('Unable to write plugin.json');
        }

        $ok = @file_put_contents($root . '/src/Hooks.php', $this->hooksPhp($ns));
        if ($ok === false) {
            $this->removeDir($root);
            throw new RuntimeException('Unable to write src/Hooks.php');
        }

        return $root;
    }

    public function namespaceFromId(string $id): string
    {
        $parts = array_map(
            static fn (string $part): string => ucfirst($part),
            explode('-', $id)
        );

        return 'PaginiumCMS\\Http\\Extensions\\' . implode('', $parts);
    }

    /**
     * @param list<string> $capabilities
     * @return list<string>
     */
    private function normalizeCapabilities(array $capabilities): array
    {
        $out = [];
        foreach ($capabilities as $item) {
            $capability = trim($item);
            if ($capability === '' || in_array($capability, $out, true)) {
                continue;
            }
            $out[] = $capability;
        }

        return $out;
    }

    private function titleFromId(string $id): string
    {
        return implode(' ', array_map('ucfirst', explode('-', $id)));
    }

    private function hooksPhp(string $namespace): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use PaginiumCMS\\Http\\Extensions\\Capabilities\\PluginRuntimeContext;

final class Hooks
{
    /**
     * @param array<string, mixed> \$context
     */
    public static function onBoot(array \$context, ?PluginRuntimeContext \$runtime = null): void
    {
    }
}

PHP;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}
