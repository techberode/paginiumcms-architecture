<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodePolicy\Contracts;

/**
 * Validates code before it is written via CodeEditor / plugins / layout Monaco (It.14 / It.58).
 */
interface CodePolicyEngineInterface
{
    /**
     * Validate content for a storage path (core or untrusted tree).
     *
     * Untrusted paths are always checked (fail-closed) even if Settings `codePolicy.enabled` is false.
     *
     * @throws \PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException
     */
    public function validate(string $path, string $content): void;

    /**
     * Force untrusted/strict policy regardless of path (Monaco buffers, imports, future theme/plugin studio).
     *
     * @throws \PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException
     */
    public function validateUntrusted(string $logicalPath, string $content): void;

    /**
     * Whether the path is treated as outside CMS core (extensions, themes, layout shortcodes, …).
     */
    public function isUntrustedPath(string $path): bool;
}
