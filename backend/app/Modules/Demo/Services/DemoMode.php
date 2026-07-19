<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Services;

/**
 * Central DEMO_MODE gate (Iteration 13).
 */
final class DemoMode
{
    public function isEnabled(): bool
    {
        return filter_var(
            getenv('DEMO_MODE') ?: ($_ENV['DEMO_MODE'] ?? false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function storageRelativePath(): string
    {
        return 'storage/app/demo';
    }
}
