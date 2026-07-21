<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\I18n\Services;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\I18n\Exception\TranslationPolicyViolationException;

/**
 * Policy checks for language catalog files before promote from staging (It.19).
 */
final class TranslationPolicyValidator
{
    public function __construct(
        private SyntaxChecker $syntaxChecker
    ) {
    }

    /**
     * @throws TranslationPolicyViolationException
     */
    public function assertValid(string $path, string $content): void
    {
        $errors = $this->collectErrors($path, $content);
        if ($errors !== []) {
            throw new TranslationPolicyViolationException($errors);
        }
    }

    /**
     * @return list<array{code: string, message: string, line?: int, hint?: string}>
     */
    public function collectErrors(string $path, string $content): array
    {
        $errors = [];

        if (strlen($content) > 512 * 1024) {
            $errors[] = [
                'code' => 'file.too_large',
                'message' => 'Translation file exceeds maximum size of 512 KB',
                'hint' => 'Split strings into smaller modules or remove unused keys.',
            ];
        }

        if (str_ends_with(strtolower($path), '.php')) {
            return [...$errors, ...$this->validatePhp($path, $content)];
        }

        if (str_ends_with(strtolower($path), '.ts')) {
            return [...$errors, ...$this->validateTypeScript($content)];
        }

        $errors[] = [
            'code' => 'file.unsupported',
            'message' => 'Unsupported translation file type',
        ];

        return $errors;
    }

    /**
     * @return list<array{code: string, message: string, line?: int, hint?: string}>
     */
    private function validatePhp(string $path, string $content): array
    {
        $errors = [];

        if (!preg_match('/^\s*<\?php/s', $content)) {
            $errors[] = [
                'code' => 'php.missing_open_tag',
                'message' => 'PHP translation file must start with <?php',
                'line' => 1,
                'hint' => '<?php' . "\n\ndeclare(strict_types=1);\n\nreturn [ ... ];",
            ];
        }

        if (!preg_match('/return\s*\[/s', $content)) {
            $errors[] = [
                'code' => 'php.missing_return_array',
                'message' => 'PHP translation file must return an array',
                'hint' => "return [\n    'key' => 'value',\n];",
            ];
        }

        if (preg_match('/\b(eval|exec|shell_exec|system|passthru|proc_open|popen)\s*\(/i', $content)) {
            $errors[] = [
                'code' => 'php.forbidden_function',
                'message' => 'Forbidden PHP function detected in translation file',
                'hint' => 'Translation files must contain string literals only.',
            ];
        }

        if (!$this->syntaxChecker->check($path, $content)) {
            $errors[] = [
                'code' => 'php.syntax',
                'message' => 'PHP syntax error: ' . ($this->syntaxChecker->getLastError() ?? 'invalid syntax'),
                'hint' => 'Run php -l locally or compare with a working lang/*.php file.',
            ];
        }

        return $errors;
    }

    /**
     * @return list<array{code: string, message: string, line?: int, hint?: string}>
     */
    private function validateTypeScript(string $content): array
    {
        $errors = [];

        if (!preg_match('/export\s+(const|let)\s+[A-Za-z0-9_]+\s*:\s*MessageTree/s', $content)) {
            $errors[] = [
                'code' => 'ts.missing_export',
                'message' => 'TypeScript catalog must export a typed MessageTree constant',
                'hint' => "export const moduleSk: MessageTree = { ... };",
            ];
        }

        if (!str_contains($content, 'MessageTree')) {
            $errors[] = [
                'code' => 'ts.missing_message_tree',
                'message' => 'Missing MessageTree type import',
                'hint' => "import type { MessageTree } from '../../types';",
            ];
        }

        if (preg_match('/\b(eval|Function\s*\(|new\s+Function)\s*\(/', $content)) {
            $errors[] = [
                'code' => 'ts.forbidden_dynamic_code',
                'message' => 'Dynamic code is not allowed in translation catalogs',
            ];
        }

        $open = substr_count($content, '{');
        $close = substr_count($content, '}');
        if ($open !== $close) {
            $errors[] = [
                'code' => 'ts.unbalanced_braces',
                'message' => 'Unbalanced curly braces in translation object',
                'hint' => 'Ensure every { has a matching }.',
            ];
        }

        if (preg_match('/^\s*import\s+[^\'"].*from\s+[\'"](?!\.)/m', $content)) {
            $errors[] = [
                'code' => 'ts.external_import',
                'message' => 'External imports are not allowed (only relative types import)',
                'hint' => 'Use only `import type { MessageTree } from \'../../types\'`.',
            ];
        }

        return $errors;
    }
}
