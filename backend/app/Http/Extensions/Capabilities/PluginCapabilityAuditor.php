<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Capabilities;

use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Records declared-capability use in the security audit log (It.89c).
 */
final class PluginCapabilityAuditor
{
    public function __construct(private ?SecurityAuditStore $audit = null)
    {
    }

    public function used(string $pluginId, string $capability): void
    {
        $plugin = LogSanitizer::value($pluginId, 64);
        $cap = LogSanitizer::value($capability, 80);
        $this->audit?->append(
            'plugin_capability',
            LogSeverity::INFO,
            'plugin ' . $plugin . ' used ' . $cap,
            null,
            null,
            null,
            [
                'pluginId' => $plugin,
                'capability' => $cap,
            ]
        );
    }
}
