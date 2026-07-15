<?php
// backend/app/Core/CodeEditor/Services/CodeEditorManager.php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Services;

use PaginiumCMS\Core\CodeEditor\Contracts\CodeEditorInterface;
use PaginiumCMS\Core\AuditTrail\Services\AuditTrailService;
use PaginiumCMS\Modules\Security\Models\User;

class CodeEditorManager implements CodeEditorInterface
{
    /** @var list<string> */
    private array $allowedPaths = [
        'backend/app/Modules',
        'backend/plugins',
        'backend/resources/views/themes',
        'backend/config',
    ];

    /** @var list<string> */
    private array $forbiddenPaths = [
        'backend/app/Core',
        'backend/bootstrap',
        'backend/vendor',
    ];

    /**
     * Koreň projektu (repozitára). Súbor je v
     * backend/app/Core/CodeEditor/Services, teda 5 úrovní nad koreňom.
     */
    private function projectRoot(): string
    {
        return dirname(__DIR__, 5);
    }

    /**
     * Lexikálne kanonicalizuje absolútnu cestu (vyrieši '.' a '..')
     * bez toho, aby cesta musela existovať na disku. Toto je jadro
     * ochrany proti path traversal - '..' segmenty sa vyriešia PRED
     * kontrolou allow/deny zoznamu, takže sa z povoleného adresára
     * nedá "vyliezť".
     */
    private function canonicalize(string $absolutePath): string
    {
        $parts = [];
        foreach (explode('/', $absolutePath) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $segment;
        }

        return '/' . implode('/', $parts);
    }

    /**
     * Overí, či je (kanonická) cesta v rámci daného základného adresára.
     */
    private function isWithin(string $canonicalPath, string $canonicalBase): bool
    {
        return $canonicalPath === $canonicalBase
            || str_starts_with($canonicalPath, $canonicalBase . '/');
    }

    public function canEdit(string $path): bool
    {
        // Null-bajt = pokus o obídenie kontrol cez C-string trunkáciu.
        if (str_contains($path, "\0")) {
            return false;
        }

        // Absolútne cesty nie sú povolené - vždy relatívne od koreňa.
        if (str_starts_with($path, '/')) {
            return false;
        }

        $root = $this->canonicalize($this->projectRoot());
        $target = $this->canonicalize($root . '/' . $path);

        // Zakázané cesty majú prednosť.
        foreach ($this->forbiddenPaths as $forbidden) {
            if ($this->isWithin($target, $this->canonicalize($root . '/' . $forbidden))) {
                return false;
            }
        }

        // Musí ležať vo vnútri niektorého povoleného adresára.
        foreach ($this->allowedPaths as $allowed) {
            if ($this->isWithin($target, $this->canonicalize($root . '/' . $allowed))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Overí prístup a vráti bezpečnú absolútnu cestu k súboru.
     */
    private function resolve(string $path): string
    {
        if (!$this->canEdit($path)) {
            throw new \RuntimeException('Prístup k súboru je zakázaný');
        }

        return $this->canonicalize($this->projectRoot() . '/' . $path);
    }

    public function readFile(string $path): string
    {
        $fullPath = $this->resolve($path);
        if (!is_file($fullPath)) {
            throw new \RuntimeException('Súbor neexistuje');
        }

        $content = file_get_contents($fullPath);
        if ($content === false) {
            throw new \RuntimeException('Súbor sa nepodarilo prečítať');
        }

        return $content;
    }

    public function writeFile(string $path, string $content): bool
    {
        $fullPath = $this->resolve($path);

        // Kontrola syntaxe
        $syntaxChecker = new SyntaxChecker();
        if (!$syntaxChecker->check($path, $content)) {
            throw new \RuntimeException('Syntax error: ' . $syntaxChecker->getLastError());
        }

        // Záloha
        $backup = new FileBackup();
        $backup->create($path);

        return file_put_contents($fullPath, $content) !== false;
    }

    /**
     * @return list<string>
     */
    public function listFiles(string $directory): array
    {
        $fullPath = $this->resolve($directory);
        if (!is_dir($fullPath)) {
            return [];
        }

        $root = $this->canonicalize($this->projectRoot());
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = ltrim(str_replace($root, '', $file->getPathname()), '/');
            }
        }

        return $files;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFileInfo(string $path): array
    {
        $fullPath = $this->resolve($path);
        if (!is_file($fullPath)) {
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

    /**
     * @return list<string>
     */
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
