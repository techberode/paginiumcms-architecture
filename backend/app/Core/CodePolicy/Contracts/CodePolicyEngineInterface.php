<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodePolicy\Contracts;

/**
 * Validates code before it is written via CodeEditor (Iteration 14).
 */
interface CodePolicyEngineInterface
{
    /**
     * @throws \PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException
     */
    public function validate(string $path, string $content): void;
}
