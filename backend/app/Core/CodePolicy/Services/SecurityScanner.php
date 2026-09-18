<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodePolicy\Services;

/**
 * Scans PHP source for forbidden function calls (Iteration 14) and untrusted indirection (It.89d).
 */
final class SecurityScanner
{
    /** @var list<string> */
    private const CALLBACK_HELPERS = [
        'array_map',
        'array_filter',
        'array_walk',
        'array_walk_recursive',
        'array_reduce',
        'usort',
        'uasort',
        'uksort',
        'preg_replace_callback',
        'register_shutdown_function',
        'register_tick_function',
        'forward_static_call',
        'forward_static_call_array',
        'iterator_apply',
        'ob_start',
    ];

    /**
     * @param list<string> $forbiddenFunctions
     * @return list<string>
     */
    public function scanPhp(string $content, array $forbiddenFunctions): array
    {
        $tokens = token_get_all($content);
        if ($tokens === []) {
            return ['Unable to tokenize PHP source'];
        }

        $forbidden = array_map('strtolower', $forbiddenFunctions);
        $violations = [];

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

            $next = $this->tokenAt($tokens, $this->nextCodeIndex($tokens, $index));
            if ($this->isOpenParen($next)) {
                $violations[] = sprintf('Forbidden PHP function: %s', $token[1]);
            }
        }

        return array_values(array_unique($violations));
    }

    /**
     * Variable functions, $$ / extract-style symbol injection, and callback helpers
     * that pass a forbidden name as a string (It.89d).
     *
     * @param list<string> $forbiddenFunctions
     * @return list<string>
     */
    public function scanUntrustedIndirection(string $content, array $forbiddenFunctions): array
    {
        $tokens = token_get_all($content);
        if ($tokens === []) {
            return [];
        }

        $forbidden = array_map('strtolower', $forbiddenFunctions);
        $helpers = self::CALLBACK_HELPERS;
        $violations = [];

        foreach ($tokens as $index => $token) {
            if ($token === '$') {
                $next = $this->tokenAt($tokens, $this->nextCodeIndex($tokens, $index));
                if (is_array($next) && $next[0] === T_VARIABLE) {
                    $violations[] = 'Forbidden variable variable ($$)';
                }
                continue;
            }

            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_DOLLAR_OPEN_CURLY_BRACES) {
                $violations[] = 'Forbidden variable variable ($$)';
                continue;
            }

            if ($token[0] === T_VARIABLE) {
                $next = $this->tokenAt($tokens, $this->nextCodeIndex($tokens, $index));
                if ($this->isOpenParen($next)) {
                    $violations[] = 'Forbidden variable function call';
                }
                continue;
            }

            if ($token[0] !== T_STRING) {
                continue;
            }

            $name = strtolower($token[1]);
            if (!in_array($name, $helpers, true)) {
                continue;
            }

            $parenIndex = $this->nextCodeIndex($tokens, $index);
            if (!$this->isOpenParen($this->tokenAt($tokens, $parenIndex))) {
                continue;
            }

            $firstArg = $this->tokenAt($tokens, $this->nextCodeIndex($tokens, $parenIndex));
            $literal = $this->stringLiteral($firstArg);
            if ($literal !== null && in_array(strtolower($literal), $forbidden, true)) {
                $violations[] = sprintf('Forbidden callback indirection: %s via %s', $literal, $token[1]);
            }
        }

        return array_values(array_unique($violations));
    }

    /**
     * @param array<int, string|array{0: int, 1: string, 2?: int}> $tokens
     */
    private function nextCodeIndex(array $tokens, int $fromExclusive): int
    {
        $i = $fromExclusive + 1;
        $count = count($tokens);
        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $i++;
                continue;
            }

            return $i;
        }

        return $count;
    }

    /**
     * @param array<int, string|array{0: int, 1: string, 2?: int}> $tokens
     */
    private function tokenAt(array $tokens, int $index): mixed
    {
        return $tokens[$index] ?? null;
    }

    private function isOpenParen(mixed $token): bool
    {
        return $token === '(' || (is_array($token) && trim((string) $token[1]) === '(');
    }

    private function stringLiteral(mixed $token): ?string
    {
        if (!is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
            return null;
        }

        $raw = $token[1];
        $length = strlen($raw);
        if ($length < 2) {
            return null;
        }

        return substr($raw, 1, $length - 2);
    }
}
