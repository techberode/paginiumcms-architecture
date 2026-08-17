<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Contracts;

use PaginiumCMS\Modules\Origin\Models\FeatureProbeResult;

interface FeatureProbeInterface
{
    public function id(): string;

    public function group(): string;

    public function labelKey(): string;

    public function run(): FeatureProbeResult;
}
