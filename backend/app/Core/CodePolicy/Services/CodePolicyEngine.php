<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodePolicy\Services;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Contracts\CodePolicyEngineInterface;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Syntax + security + size policy for CodeEditor writes (Iteration 14).
 */
final class CodePolicyEngine implements CodePolicyEngineInterface
{
    private const DEFAULT_FORBIDDEN = [
        'eval',
        'exec',
        'shell_exec',
        'system',
        'passthru',
        'proc_open',
        'popen',
        'assert',
        'create_function',
    ];

    /**
     * Prísnejší zoznam pre extension/plugin kód (untrusted ZIP import).
     * Tu zakazujeme aj RCE primitíva, ktoré sú v jadre CMS legitímne
     * (include/require pri bootstrappingu, unserialize v session storage),
     * ale v pluginoch predstavujú priamu cestu k spusteniu ľubovoľného kódu.
     */
    private const EXTENSION_FORBIDDEN = [
        'unserialize',
        'call_user_func',
        'call_user_func_array',
        'include',
        'include_once',
        'require',
        'require_once',
    ];

    private const EXTENSION_PATH_MARKER = 'backend/app/Http/Extensions/';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private SyntaxChecker $syntaxChecker,
        private SecurityScanner $securityScanner
    ) {
    }

    public function validate(string $path, string $content): void
    {
        $policy = $this->settings->group('codePolicy');
        if (!(bool) ($policy['enabled'] ?? true)) {
            return;
        }

        $errors = [];

        $maxKb = (int) ($policy['maxFileSizeKb'] ?? 512);
        if (strlen($content) > $maxKb * 1024) {
            $errors['size'][] = sprintf('File exceeds maximum size of %d KB', $maxKb);
        }

        if (!$this->syntaxChecker->check($path, $content)) {
            $errors['syntax'][] = (string) ($this->syntaxChecker->getLastError() ?? 'Syntax check failed');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'php') {
            $forbidden = $this->parseForbiddenList((string) ($policy['forbiddenPhpFunctions'] ?? ''));

            // Extension/plugin kód (untrusted) dostáva prísnejší zoznam.
            $isExtensionPath = str_contains(str_replace('\\', '/', $path), self::EXTENSION_PATH_MARKER);
            if ($isExtensionPath) {
                $forbidden = array_values(array_unique(array_merge($forbidden, self::EXTENSION_FORBIDDEN)));
            }

            foreach ($this->securityScanner->scanPhp($content, $forbidden) as $violation) {
                $errors['security'][] = $violation;
            }

            if ((bool) ($policy['strictMode'] ?? false) && str_contains($path, self::EXTENSION_PATH_MARKER)) {
                if (!preg_match('/namespace\s+PaginiumCMS\\\\Http\\\\Extensions\\\\[A-Za-z0-9_]+;/', $content)) {
                    $errors['compatibility'][] = 'Extension PHP files must declare namespace PaginiumCMS\\Http\\Extensions\\{id}';
                }
            }
        }

        if ($errors !== []) {
            throw new CodePolicyViolationException($errors);
        }
    }

    /**
     * @return list<string>
     */
    private function parseForbiddenList(string $raw): array
    {
        if (trim($raw) === '') {
            return self::DEFAULT_FORBIDDEN;
        }

        $items = preg_split('/[\s,]+/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $items)));
    }
}
