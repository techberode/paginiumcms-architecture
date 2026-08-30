<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

final class BulkSelectionUxFeatureProbe extends AbstractFeatureProbe
{
    public function id(): string
    {
        return 'it.86.bulk_selection';
    }

    public function group(): string
    {
        return 'platform';
    }

    public function labelKey(): string
    {
        return 'origin.probes.it86_bulk_selection';
    }

    public function run(): FeatureProbeResult
    {
        if (!$this->support->frontendSourceContains('components/backend/BulkActionBar.tsx', 'totalCount')) {
            return $this->missing('BulkActionBar missing selected-of-total counter.');
        }

        if (!$this->support->frontendSourceContains('utils/bulkSelectionLabel.ts', 'bulkSelectionCounts')) {
            return $this->missing('bulkSelectionCounts helper is not registered.');
        }

        return $this->implemented('Bulk bars show selected-of-total counts with confirm ratios.', 'unreleased');
    }
}
