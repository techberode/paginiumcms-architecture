<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodePolicy\Services;

/**
 * Scans PHP source for forbidden function calls (Iteration 14).
 */
final class SecurityScanner
{
    /**
     * @param list<string> $forbiddenFunctions
     * @return list<string>
     */
    public function scanPhp(string $content, array $forbiddenFunctions): array
    {
        $violations = [];
        $tokens = token_get_all($content);
        if ($tokens === []) {
            return ['Unable to tokenize PHP source'];
        }

        $forbidden = array_map('strtolower', $forbiddenFunctions);

        // Jazykové konštrukty (nie sú T_STRING) → mapujeme token typ na kľúčové slovo.
        $constructTokens = [
            T_EVAL => 'eval',
            T_INCLUDE => 'include',
            T_INCLUDE_ONCE => 'include_once',
            T_REQUIRE => 'require',
            T_REQUIRE_ONCE => 'require_once',
        ];

        foreach ($tokens as $index => $token) {
            if (!is_array($token)) {
                continue;
            }

            if (isset($constructTokens[$token[0]])) {
                $keyword = $constructTokens[$token[0]];
                if (in_array($keyword, $forbidden, true)) {
                    $violations[] = 'Forbidden PHP construct: ' . $keyword;
                }
                continue;
            }

            if (defined('T_ASSERT') && $token[0] === T_ASSERT && in_array('assert', $forbidden, true)) {
                $violations[] = 'Forbidden PHP function: assert';
                continue;
            }

            if ($token[0] !== T_STRING) {
                continue;
            }

            $name = strtolower($token[1]);
            if (!in_array($name, $forbidden, true)) {
                continue;
            }

            $next = $tokens[$index + 1] ?? null;
            if ($next === '(' || (is_array($next) && trim((string) $next[1]) === '(')) {
                $violations[] = sprintf('Forbidden PHP function: %s', $token[1]);
            }
        }

        return array_values(array_unique($violations));
    }
}
