<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class ArticlePrintFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.86.article_print';
    }

    public function group(): string
    {
        return 'content';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it86_article_print';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->appSourceContains('Core/Settings/SettingsSchema.php', 'articlePrintEnabled')) {
            return $this->missing('Settings schema missing content.articlePrintEnabled.');
        }

        if (!$this->support->frontendSourceContains('components/frontend/BlogRenderer.tsx', 'articlePrintEnabled')) {
            return $this->missing('BlogRenderer does not expose print action.');
        }

        if (!$this->support->frontendSourceContains('theme/pgLayout.css', '@media print')) {
            return $this->partial('Print stylesheet hooks missing in pgLayout.css.');
        }

        return $this->implemented('Article print toggle and public print CSS are wired.', 'unreleased');
    }
}
