<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Security;

/**
 * Extra CSP script-src tokens (It.87m). Fail-closed: empty list when theme scripts are off.
 */
interface CspScriptSrcContributorInterface
{
    /**
     * @return list<string> tokens such as "'sha384-…'"
     */
    public function extraScriptSrcTokens(): array;
}
