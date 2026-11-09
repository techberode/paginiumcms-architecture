<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Core\Forms\Services\FormDefinitionRepository;
use PaginiumCMS\Core\Forms\Services\FormEmbedRenderer;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class FormBuilderFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.101.form_builder';
    }

    public function group(): string
    {
        return 'layout';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it101_form_builder';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->classAvailable(FormDefinitionRepository::class)
            || !$this->support->classAvailable(FormEmbedRenderer::class)) {
            return $this->missing('Form builder services are missing.');
        }

        if (!$this->support->routeFileContains('forms.php', 'FormController')) {
            return $this->partial('Form services exist; admin routes may be incomplete.');
        }

        if (!$this->support->routeFileContains('public-forms.php', 'PublicFormController')) {
            return $this->partial('Admin forms exist; public submit API may be incomplete.');
        }

        return $this->implemented('Public form builder, embed shortcode, and desk routing are wired.');
    }
}
