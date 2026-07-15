<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Services;

use PaginiumCMS\Core\CodeEditor\Contracts\CodeEditorInterface;
use PaginiumCMS\Core\CodePolicy\Contracts\CodePolicyEngineInterface;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
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
        ?string $projectRoot = null
    ) {
        $this->projectRoot = rtrim($projectRoot ?? dirname(__DIR__, 5), '/');
    }

    public function getDefaultDirectory(): string
    {
        return $this->allowedPaths[0];
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
            $this->codePolicy->validate($path, $content);
        } catch (CodePolicyViolationException $e) {
            $this->logger->logError($path, $e);
            throw $e;
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
     * @return list<array<string, mixed>>
     */
    public function listFiles(string $directory): array
    {
        if ($directory === '') {
            $directory = $this->getDefaultDirectory();
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

    public function getFileInfo(string $path): array
    {
        $fullPath = $this->resolveExistingPath($path);

        return $this->buildFileInfo($path, $fullPath);
    }

    /**
     * @return list<string>
     */
    public function getBackups(string $path): array
    {
        if (!$this->canEdit($path)) {
            throw new RuntimeException('Access to file is denied');
        }

        return array_map('basename', $this->backup->getBackups($path));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFileInfo(string $relativePath, string $fullPath): array
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
}
