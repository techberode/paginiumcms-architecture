<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Services;

use PaginiumCMS\Core\CodePolicy\Contracts\CodePolicyEngineInterface;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Validates all files in an extension tree via CodePolicyEngine (It.15b).
 */
final class PluginPolicyScanner
{
    public function __construct(
        private CodePolicyEngineInterface $codePolicy,
    ) {
    }

    /**
     * @return array<string, list<string>> errors keyed by relative file path
     */
    public function scanDirectory(string $absoluteRoot, string $policyPathPrefix): array
    {
        $errors = [];
        $prefix = rtrim(str_replace('\\', '/', $policyPathPrefix), '/');

        foreach ($this->iterateFiles($absoluteRoot) as $relativePath => $absolutePath) {
            $content = @file_get_contents($absolutePath);
            if ($content === false) {
                $errors[$relativePath][] = 'Unable to read file';
                continue;
            }

            $policyPath = $prefix . '/' . $relativePath;

            try {
                $this->codePolicy->validate($policyPath, $content);
            } catch (CodePolicyViolationException $exception) {
                $errors[$relativePath] = $this->flattenErrors($exception->getErrors());
            }
        }

        return $errors;
    }

    /**
     * @return array<string, string> relative path => absolute path
     */
    private function iterateFiles(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $root = rtrim($root, '/');
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $absolute = $file->getPathname();
            $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($root))), '/');
            if ($relative === '') {
                continue;
            }

            $files[$relative] = $absolute;
        }

        ksort($files);

        return $files;
    }

    /**
     * @param array<string, list<string>> $grouped
     * @return list<string>
     */
    private function flattenErrors(array $grouped): array
    {
        $flat = [];
        foreach ($grouped as $messages) {
            foreach ($messages as $message) {
                $flat[] = (string) $message;
            }
        }

        return $flat;
    }
}
