<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodePolicy\Services;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Contracts\CodePolicyEngineInterface;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Syntax + security + size policy for CodeEditor / plugins / layout Monaco.
 *
 * Untrusted trees (extensions, themes, layout shortcodes, …) are fail-closed:
 * policy always runs, even if Settings `codePolicy.enabled` is false.
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
     * Stricter list for untrusted code (plugins, themes, Monaco-authored artifacts).
     * Blocks RCE primitives that are legitimate in core bootstrap only.
     */
    private const UNTRUSTED_FORBIDDEN = [
        'unserialize',
        'call_user_func',
        'call_user_func_array',
        'include',
        'include_once',
        'require',
        'require_once',
    ];

    /**
     * Path markers (normalized with forward slashes) for content outside CMS core.
     *
     * @var list<string>
     */
    private const UNTRUSTED_PATH_MARKERS = [
        'backend/app/Http/Extensions/',
        'Http/Extensions/',
        'themes/',
        'data/layout/',
        'data/shortcodes/',
        'data/plugins/',
        'untrusted://',
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
        $this->runValidation($path, $content, $this->isUntrustedPath($path));
    }

    public function validateUntrusted(string $logicalPath, string $content): void
    {
        $path = $logicalPath;
        if (!$this->isUntrustedPath($path)) {
            $path = 'untrusted://' . ltrim(str_replace('\\', '/', $logicalPath), '/');
        }

        $this->runValidation($path, $content, true);
    }

    public function isUntrustedPath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);
        foreach (self::UNTRUSTED_PATH_MARKERS as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function runValidation(string $path, string $content, bool $untrusted): void
    {
        $policy = $this->settings->group('codePolicy');

        // Core may opt out; untrusted never may (fail-closed).
        if (!$untrusted && !(bool) ($policy['enabled'] ?? true)) {
            return;
        }

        $errors = [];

        $maxKb = (int) ($policy['maxFileSizeKb'] ?? 512);
        if ($untrusted) {
            // Tighter default cap for non-core artifacts.
            $maxKb = min($maxKb, (int) ($policy['untrustedMaxFileSizeKb'] ?? 256));
        }

        if (strlen($content) > $maxKb * 1024) {
            $errors['size'][] = sprintf('File exceeds maximum size of %d KB', $maxKb);
        }

        if (!$this->syntaxChecker->check($path, $content)) {
            $errors['syntax'][] = (string) ($this->syntaxChecker->getLastError() ?? 'Syntax check failed');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'php' || str_ends_with(strtolower($path), '.php')) {
            $forbidden = $this->parseForbiddenList((string) ($policy['forbiddenPhpFunctions'] ?? ''));
            if ($untrusted) {
                $forbidden = array_values(array_unique(array_merge($forbidden, self::UNTRUSTED_FORBIDDEN)));
            }

            foreach ($this->securityScanner->scanPhp($content, $forbidden) as $violation) {
                $errors['security'][] = $violation;
            }

            if ($untrusted) {
                if (!str_contains($content, 'declare(strict_types=1)')) {
                    $errors['compatibility'][] = 'Untrusted PHP must declare strict_types=1';
                }
            }

            $normalized = str_replace('\\', '/', $path);
            $strict = $untrusted || (bool) ($policy['strictMode'] ?? false);
            if (
                $strict
                && str_contains($normalized, self::EXTENSION_PATH_MARKER)
                && $this->requiresExtensionNamespace($normalized)
            ) {
                if (!preg_match('/namespace\s+PaginiumCMS\\\\Http\\\\Extensions\\\\[A-Za-z0-9_]+;/', $content)) {
                    $errors['compatibility'][] = 'Extension PHP class files must declare namespace PaginiumCMS\\Http\\Extensions\\{id}';
                }
            }
        }

        if ($errors !== []) {
            throw new CodePolicyViolationException($errors);
        }
    }

    /**
     * routes.php / bootstrap stubs are closures without a class namespace; class files under src/ must declare one.
     */
    private function requiresExtensionNamespace(string $normalizedPath): bool
    {
        $base = basename($normalizedPath);
        if ($base === 'routes.php' || $base === 'bootstrap.php') {
            return false;
        }

        return str_contains($normalizedPath, '/src/') || str_contains($normalizedPath, '/Src/');
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
