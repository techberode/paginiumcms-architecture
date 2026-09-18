<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Capabilities;

use PaginiumCMS\Support\Lang;
use RuntimeException;

/**
 * Plugin called a platform API it did not declare in plugin.json capabilities (It.89b).
 */
final class PluginCapabilityDeniedException extends RuntimeException
{
    public function __construct(
        private string $pluginId,
        private string $capability,
    ) {
        parent::__construct(Lang::get('capability_denied', [
            'plugin' => $pluginId,
            'capability' => $capability,
        ], 'extensions'));
    }

    public function pluginId(): string
    {
        return $this->pluginId;
    }

    public function capability(): string
    {
        return $this->capability;
    }
}
