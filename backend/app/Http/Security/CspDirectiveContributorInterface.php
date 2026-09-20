<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Security;

/**
 * Optional CSP extras (It.95 playground CDN). Fail-closed: empty when the feature is off.
 */
interface CspDirectiveContributorInterface
{
    /**
     * @return list<string>
     */
    public function extraConnectSrcTokens(): array;

    public function frameSrcDirective(): ?string;
}
