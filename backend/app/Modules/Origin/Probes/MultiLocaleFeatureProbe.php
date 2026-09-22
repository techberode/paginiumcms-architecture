<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class MultiLocaleFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.73.multi_locale';
    }

    public function group(): string
    {
        return 'content';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it73_locale';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->routeFileContains('translations.php', 'TranslationController')) {
            return $this->missing('Translation admin routes are not registered.');
        }

        if (!$this->support->classAvailable(\PaginiumCMS\Core\Content\LocalizedContentWriter::class)) {
            return $this->partial('Locale admin exists; localized content writer should be verified.');
        }

        return $this->implemented('Multi-locale admin routes and localized content pipeline are present.', '2.1.0-beta.29');
    }
}
