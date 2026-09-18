<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Capabilities;

/**
 * Allow-list of plugin capability strings (It.89a). Runtime broker It.89b; SafeHookRunner It.89c; scanner It.89d; CLI It.89e.
 */
final class PluginCapabilityCatalog
{
    public const CONTENT_READ = 'content:read';
    public const CONTENT_WRITE = 'content:write';
    public const CONTENT_WRITE_OWN = 'content:write:own';
    public const MEDIA_READ = 'media:read';
    public const MEDIA_WRITE = 'media:write';
    public const SETTINGS_READ = 'settings:read';
    public const SETTINGS_WRITE = 'settings:write';
    public const ADMIN_UI_SIDEBAR_WIDGET = 'admin-ui:sidebar-widget';
    public const ADMIN_UI_EDITOR_BLOCK = 'admin-ui:editor-block';

    public const OUTBOUND_PREFIX = 'network:outbound:';

    /**
     * Exact (non-parameterized) capability ids.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CONTENT_READ,
            self::CONTENT_WRITE,
            self::CONTENT_WRITE_OWN,
            self::MEDIA_READ,
            self::MEDIA_WRITE,
            self::SETTINGS_READ,
            self::SETTINGS_WRITE,
            self::ADMIN_UI_SIDEBAR_WIDGET,
            self::ADMIN_UI_EDITOR_BLOCK,
        ];
    }

    public static function isAllowed(string $capability): bool
    {
        if (in_array($capability, self::all(), true)) {
            return true;
        }

        return self::isOutboundNetwork($capability);
    }

    public static function isOutboundNetwork(string $capability): bool
    {
        if (!str_starts_with($capability, self::OUTBOUND_PREFIX)) {
            return false;
        }

        $host = substr($capability, strlen(self::OUTBOUND_PREFIX));
        if ($host === '' || str_contains($host, '/') || str_contains($host, ':') || str_contains($host, '*')) {
            return false;
        }

        return filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}
