<?php
// backend/app/Core/CodeEditor/Services/CodeEditorManager.php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Services;

use PaginiumCMS\Core\CodeEditor\Contracts\CodeEditorInterface;
use PaginiumCMS\Core\AuditTrail\Services\AuditTrailService;
use PaginiumCMS\Modules\Security\Models\User;

class CodeEditorManager implements CodeEditorInterface
{
    private array $allowedPaths = [
        'backend/app/Modules',
        'backend/plugins',
        'backend/resources/views/themes',
        'backend/config',
    ];

    private array $forbiddenPaths = [
        'backend/app/Core',
        'backend/bootstrap',
        'backend/vendor',
    ];

    public function canEdit(string $path): bool
    {
        // Kontrola, či cesta nie je v zakázaných
        foreach ($this->forbiddenPaths as $forbidden) {
            if (strpos($path, $forbidden) === 0) {
                return false;
            }
        }

        // Kontrola, či cesta je v povolených
        foreach ($this->allowedPaths as $allowed) {
            if (strpos($path, $allowed) === 0) {
                return true;
            }
        }

        return false;
    }

    public function readFile(string $path): string
    {
        if (!$this->canEdit($path)) {
            throw new \RuntimeException('Prístup k súboru je zakázaný');
        }

        $fullPath = __DIR__ . '/../../../' . $path;
        if (!file_exists($fullPath)) {
            throw new \RuntimeException('Súbor neexistuje');
        }

        return file_get_contents($fullPath);
    }

    public function writeFile(string $path, string $content): bool
    {
        if (!$this->canEdit($path)) {
            throw new \RuntimeException('Prístup k súboru je zakázaný');
        }

        // Kontrola syntaxe
        $syntaxChecker = new SyntaxChecker();
        if (!$syntaxChecker->check($path, $content)) {
            throw new \RuntimeException('Syntax error: ' . $syntaxChecker->getLastError());
        }

        // Záloha
        $backup = new FileBackup();
        $backup->create($path);

        $fullPath = __DIR__ . '/../../../' . $path;
        return file_put_contents($fullPath, $content) !== false;
    }

    public function listFiles(string $directory): array
    {
        if (!$this->canEdit($directory)) {
            throw new \RuntimeException('Prístup k adresáru je zakázaný');
        }

        $fullPath = __DIR__ . '/../../../' . $directory;
        if (!is_dir($fullPath)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = str_replace(__DIR__ . '/../../../', '', $file->getPathname());
            }
        }

        return $files;
    }

    public function getFileInfo(string $path): array
    {
        if (!$this->canEdit($path)) {
            throw new \RuntimeException('Prístup k súboru je zakázaný');
        }

        $fullPath = __DIR__ . '/../../../' . $path;
        if (!file_exists($fullPath)) {
            throw new \RuntimeException('Súbor neexistuje');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $backup = new FileBackup();

        return [
            'path' => $path,
            'name' => basename($path),
            'size' => filesize($fullPath),
            'modified' => filemtime($fullPath),
            'extension' => $extension,
            'language' => $this->detectLanguage($extension),
            'editable' => true,
            'backups' => array_map('basename', $backup->getBackups($path)),
        ];
    }

    public function getBackups(string $path): array
    {
        if (!$this->canEdit($path)) {
            throw new \RuntimeException('Prístup k súboru je zakázaný');
        }

        $backup = new FileBackup();

        return array_map('basename', $backup->getBackups($path));
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
