<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Services;

use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityUsageScanner;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Same validation engine as ZIP import: manifest + code policy + capability usage (It.89e).
 *
 * @phpstan-type ScanReport array{
 *     id: string,
 *     manifest: array<string, mixed>,
 *     errors: array<string, list<string>>
 * }
 */
final class PluginScanService
{
    public function __construct(
        private PluginPolicyScanner $policyScanner,
        private ExtensionManifestValidator $manifestValidator,
        private PluginCapabilityUsageScanner $usageScanner,
    ) {
    }

    /**
     * @return ScanReport
     */
    public function scan(string $absoluteRoot): array
    {
        $root = rtrim(str_replace('\\', '/', $absoluteRoot), '/');
        $manifestPath = $root . '/plugin.json';
        if (!is_file($manifestPath)) {
            throw new RuntimeException('plugin.json not found in ' . $root);
        }

        $raw = @file_get_contents($manifestPath);
        if ($raw === false || trim($raw) === '') {
            throw new RuntimeException('plugin.json is empty or unreadable.');
        }

        try {
            /** @var array<string, mixed> $manifest */
            $manifest = JsonHelper::decode($raw);
        } catch (\JsonException $exception) {
            throw new RuntimeException('plugin.json is invalid JSON: ' . $exception->getMessage());
        }

        $id = $this->manifestValidator->validate($manifest, basename($root));
        $policyPrefix = 'backend/app/Http/Extensions/' . $id;
        $errors = $this->policyScanner->scanDirectory($root, $policyPrefix);
        $usageErrors = $this->usageScanner->scan($root, $manifest);
        foreach ($usageErrors as $file => $messages) {
            foreach ($messages as $message) {
                $errors[$file][] = $message;
            }
        }

        return [
            'id' => $id,
            'manifest' => $manifest,
            'errors' => $errors,
        ];
    }
}
