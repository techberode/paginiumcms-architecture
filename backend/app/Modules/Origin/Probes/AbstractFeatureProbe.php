<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Probes;

use PaginiumCMS\Modules\Origin\Contracts\FeatureProbeInterface;
use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;
use PaginiumCMS\Modules\Origin\Services\ProbeSupport;

abstract class AbstractFeatureProbe implements FeatureProbeInterface
{
    public function __construct(protected ProbeSupport $support)
    {
    }

    protected function implemented(string $message, ?string $since = null): FeatureProbeResult
    {
        return new FeatureProbeResult('implemented', $message, $since);
    }

    protected function partial(string $message, ?string $since = null): FeatureProbeResult
    {
        return new FeatureProbeResult('partial', $message, $since);
    }

    protected function missing(string $message): FeatureProbeResult
    {
        return new FeatureProbeResult('missing', $message);
    }
}
