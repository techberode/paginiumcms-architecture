<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\Layout\Services\ShortcodeExpanderService;
use PaginiumCMS\Core\Layout\Services\ShortcodeRegistry;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class ShortcodesFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.58.shortcodes';
    }

    public function group(): string
    {
        return 'layout';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it58_shortcodes';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(ShortcodeRegistry::class)
            || !$this->support->classAvailable(ShortcodeExpanderService::class)) {
            return $this->missing('Shortcode registry or expander is missing.');
        }

        if (!$this->support->routeFileContains('shortcodes.php', 'ShortcodeController')) {
            return $this->partial('Shortcode services exist; admin routes may be incomplete.');
        }

        return $this->implemented('Shortcode expander and admin API are wired.', '2.1.0-beta.41');
    }
}
