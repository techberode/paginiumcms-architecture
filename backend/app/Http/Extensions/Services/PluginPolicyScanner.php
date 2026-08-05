<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Services;

use PaginiumCMS\Core\CodePolicy\Services\UntrustedPolicyScanner;

/**
 * Validates all files in an extension tree via CodePolicyEngine (It.15b).
 *
 * @deprecated Use UntrustedPolicyScanner directly for new import pipelines.
 */
final class PluginPolicyScanner
{
    public function __construct(
        private UntrustedPolicyScanner $scanner,
    ) {
    }

    /**
     * @return array<string, list<string>> errors keyed by relative file path
     */
    public function scanDirectory(string $absoluteRoot, string $policyPathPrefix): array
    {
        return $this->scanner->scanDirectory($absoluteRoot, $policyPathPrefix);
    }
}
