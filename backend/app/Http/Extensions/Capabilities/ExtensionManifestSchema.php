<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Capabilities;

/**
 * Contract for plugin.json fields added in It.89a.
 */
final class ExtensionManifestSchema
{
    public const MANIFEST_VERSION = 1;

    /**
     * @return array<string, mixed>
     */
    public static function describe(): array
    {
        return [
            'manifestVersion' => [
                'type' => 'integer',
                'const' => self::MANIFEST_VERSION,
                'required' => true,
            ],
            'capabilities' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'required' => true,
                'uniqueItems' => true,
                'catalog' => PluginCapabilityCatalog::all(),
                'patterns' => [PluginCapabilityCatalog::OUTBOUND_PREFIX . '{hostname}'],
            ],
        ];
    }
}
