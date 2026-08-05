<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Services;

use PaginiumCMS\Core\CodeEditor\Contracts\CodeEditorInterface;
use PaginiumCMS\Core\CodePolicy\Contracts\CodePolicyEngineInterface;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\ShortcodeDefinitionPolicy;
use RuntimeException;

/**
 * Code editor file operations with project-root path resolution (Iteration 14).
 */
final class CodeEditorManager implements CodeEditorInterface
{
    private string $projectRoot;

    /** @var list<string> */
    private array $allowedPaths = [
        'backend/app/Modules',
        'backend/app/Http/Extensions',
        'backend/resources/views/themes',
        'backend/config',
        'data/shortcodes/definitions',
        'data/layout',
    ];

    /** @var list<string> */
    private array $forbiddenPaths = [
        'backend/app/Core',
        'backend/bootstrap',
        'backend/vendor',
    ];

    public function __construct(
        private SyntaxChecker $syntaxChecker,
        private FileBackup $backup,
        private CodeEditorLogger $logger,
        private CodePolicyEngineInterface $codePolicy,
        private ShortcodeDefinitionPolicy $shortcodePolicy,
        ?string $projectRoot = null
    ) {
        $this->projectRoot = rtrim($projectRoot ?? dirname(__DIR__, 5), '/');
    }

    public function getDefaultDirectory(): string
    {
        return $this->allowedPaths[0];
    }

    /**
     * @return list<string>
     */
    public function getAllowedDirectories(): array
    {
        return $this->allowedPaths;
    }

    /**
     * Rekurzívne načíta všetky súbory zo striktne povolených koreňov.
     *
     * @return list<array<string, mixed>>
     */
    public function listAllAllowedFiles(): array
    {
        $merged = [];

        foreach ($this->allowedPaths as $directory) {
            foreach ($this->listFiles($directory) as $file) {
                $merged[(string) $file['path']] = $file;
            }
        }

        $files = array_values($merged);
        usort($files, static fn (array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));

        return $files;
    }

    public function canEdit(string $path): bool
    {
        $normalized = $this->normalizeRelativePath($path);
        if ($normalized === null) {
            return false;
        }

        foreach ($this->forbiddenPaths as $forbidden) {
            if (str_starts_with($normalized, $forbidden)) {
                return false;
            }
        }

        foreach ($this->allowedPaths as $allowed) {
            if (str_starts_with($normalized, $allowed)) {
                return true;
            }
        }

        return false;
    }

    public function readFile(string $path): string
    {
        $fullPath = $this->resolveExistingPath($path);
        $this->logger->logFileAccess($path, 'read');

        return (string) file_get_contents($fullPath);
    }

    public function writeFile(string $path, string $content): bool
    {
        $fullPath = $this->resolveWritablePath($path);

        try {
            if ($this->codePolicy->isUntrustedPath($path)) {
                $this->codePolicy->validateUntrusted($path, $content);
            } else {
                $this->codePolicy->validate($path, $content);
            }

            if ($this->isShortcodeDefinitionPath($path)) {
                $this->shortcodePolicy->validateJson($content);
            }
        } catch (CodePolicyViolationException $e) {
            try {
                $this->logger->logPolicyRejection($path, $e);
            } catch (\Throwable) {
                // Policy rejection must propagate even if audit logging fails.
            }
            throw $e;
        }

        if (!$this->syntaxChecker->check($path, $content)) {
            throw new RuntimeException(
                'Syntax check failed: ' . ($this->syntaxChecker->getLastError() ?? 'unknown error')
            );
        }

        if (file_exists($fullPath)) {
            $this->backup->create($path);
        }

        $written = file_put_contents($fullPath, $content);
        if ($written === false) {
            throw new RuntimeException('Failed to write file');
        }

        $this->logger->logFileChange($path, 'write', [
            'bytes' => $written,
        ]);

        return true;
    }

    /**
     * @return list<array<int|string, mixed>>
 * @return array<int|string, mixed>
 */    public function listFiles(string $directory): array
    {
        if ($directory === '' || $directory === 'all' || $directory === '*') {
            return $this->listAllAllowedFiles();
        }

        if (!$this->canEdit($directory)) {
            throw new RuntimeException('Access to path is denied');
        }

        $normalized = (string) $this->normalizeRelativePath($directory);
        $candidate = $this->projectRoot . '/' . $normalized;
        if (!is_dir($candidate)) {
            return [];
        }

        $fullPath = $this->resolveExistingPath($directory, allowDirectory: true);
        if (!is_dir($fullPath)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $relative = $this->toRelativePath($file->getPathname());
            if ($relative === null || !$this->canEdit($relative)) {
                continue;
            }

            $files[] = $this->buildFileInfo($relative, $file->getPathname());
        }

        usort($files, static fn (array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));

        return $files;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getFileInfo(string $path): array
    {
        $fullPath = $this->resolveExistingPath($path);

        return $this->buildFileInfo($path, $fullPath);
    }

    /**
     * @return list<string>
 * @return array<int|string, mixed>
 */public function getBackups(string $path): array
    {
        if (!$this->canEdit($path)) {
            throw new RuntimeException('Access to file is denied');
        }

        return array_map('basename', $this->backup->getBackups($path));
    }

    public function createFile(string $path, string $content = ''): bool
    {
        $normalized = $this->normalizeRelativePath($path);
        if ($normalized === null || !$this->canEdit($normalized)) {
            throw new RuntimeException('Access to path is denied');
        }

        $fullPath = $this->projectRoot . '/' . $normalized;
        if (file_exists($fullPath)) {
            throw new RuntimeException('File already exists');
        }

        return $this->writeFile($normalized, $content);
    }

    public function deleteFile(string $path): bool
    {
        $fullPath = $this->resolveExistingPath($path);
        $this->backup->create($path);

        if (!unlink($fullPath)) {
            throw new RuntimeException('Failed to delete file');
        }

        $this->logger->logFileChange($path, 'delete', []);

        return true;
    }

    public function restoreBackup(string $path, string $backupBasename): bool
    {
        if (!$this->canEdit($path)) {
            throw new RuntimeException('Access to file is denied');
        }

        $backupPath = $this->backup->resolveBackupByBasename($path, $backupBasename);
        if ($backupPath === null) {
            throw new RuntimeException('Backup not found');
        }

        $content = file_get_contents($backupPath);
        if ($content === false) {
            throw new RuntimeException('Unable to read backup file');
        }

        return $this->writeFile($path, $content);
    }

    /**
     * @return array<int|string, mixed>
     */private function buildFileInfo(string $relativePath, string $fullPath): array
    {
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);

        return [
            'path' => $relativePath,
            'name' => basename($relativePath),
            'size' => (int) filesize($fullPath),
            'modified' => (int) filemtime($fullPath),
            'extension' => $extension,
            'language' => $this->detectLanguage($extension),
            'editable' => true,
            'backups' => array_map('basename', $this->backup->getBackups($relativePath)),
        ];
    }

    private function resolveExistingPath(string $path, bool $allowDirectory = false): string
    {
        if (!$this->canEdit($path)) {
            throw new RuntimeException('Access to path is denied');
        }

        $fullPath = $this->projectRoot . '/' . $this->normalizeRelativePath($path);
        $real = realpath($fullPath);
        if ($real === false || !str_starts_with($real, $this->projectRoot . '/')) {
            throw new RuntimeException('Path does not exist');
        }

        if (!$allowDirectory && is_dir($real)) {
            throw new RuntimeException('Path is a directory');
        }

        if (!$allowDirectory && !is_file($real)) {
            throw new RuntimeException('File does not exist');
        }

        return $real;
    }

    private function resolveWritablePath(string $path): string
    {
        if (!$this->canEdit($path)) {
            throw new RuntimeException('Access to path is denied');
        }

        $normalized = (string) $this->normalizeRelativePath($path);
        $fullPath = $this->projectRoot . '/' . $normalized;
        $dir = dirname($fullPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create target directory');
        }

        $realDir = realpath($dir);
        if ($realDir === false || !str_starts_with($realDir, $this->projectRoot . '/')) {
            throw new RuntimeException('Target directory escapes project root');
        }

        return $fullPath;
    }

    private function normalizeRelativePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            return null;
        }

        return $path;
    }

    private function toRelativePath(string $absolutePath): ?string
    {
        $real = realpath($absolutePath);
        if ($real === false || !str_starts_with($real, $this->projectRoot . '/')) {
            return null;
        }

        return substr($real, strlen($this->projectRoot) + 1);
    }

    private function detectLanguage(string $extension): string
    {
        return match (strtolower($extension)) {
            'php' => 'php',
            'js', 'jsx' => 'javascript',
            'ts', 'tsx' => 'typescript',
            'css' => 'css',
            'html', 'htm' => 'html',
            'json' => 'json',
            'md' => 'markdown',
            'yaml', 'yml' => 'yaml',
            default => 'plaintext',
        };
    }

    private function isShortcodeDefinitionPath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);

        return str_starts_with($normalized, 'data/shortcodes/definitions/')
            && str_ends_with(strtolower($normalized), '.json');
    }
}
