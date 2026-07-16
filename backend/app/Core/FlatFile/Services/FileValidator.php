<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;

class FileValidator
{
    private string $basePath;
    /** @var array<int|string, mixed> */
    private array $forbiddenPatterns = [
        '/\.\.\//',
        '/^\/\//',
        '/[<>:"|?*]/',
    ];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function validatePath(string $relativePath): void
    {
        // Prázdna cesta je povolená (znamená koreňový adresár)
        if (empty($relativePath) || $relativePath === '.') {
            return;
        }

        if (strpos($relativePath, '..') !== false) {
            throw new InvalidPathException($relativePath, 'Pokus o path traversal');
        }

        foreach ($this->forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $relativePath)) {
                throw new InvalidPathException($relativePath, 'Obsahuje zakázané znaky');
            }
        }

        if (strpos($relativePath, $this->basePath) === 0) {
            throw new InvalidPathException($relativePath, 'Cesta nemôže byť absolútna');
        }
    }

    public function getAbsolutePath(string $relativePath): string
    {
        $this->validatePath($relativePath);

        // Ak je cesta prázdna, vrátime základný adresár
        if (empty($relativePath) || $relativePath === '.') {
            return $this->basePath;
        }

        return $this->basePath . '/' . ltrim($relativePath, '/');
    }

    /**
     * Získa základnú cestu.
     *
     * @return string Základná cesta.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Zistí, či súbor existuje.
     *
     * @param string $relativePath Relatívna cesta.
     * @return bool TRUE ak súbor existuje.
     */
    public function fileExists(string $relativePath): bool
    {
        try {
            $absolutePath = $this->getAbsolutePath($relativePath);
            return file_exists($absolutePath) && is_file($absolutePath);
        } catch (InvalidPathException) {
            return false;
        }
    }

    /**
     * Zistí, či adresár existuje.
     *
     * @param string $relativePath Relatívna cesta.
     * @return bool TRUE ak adresár existuje.
     */
    public function directoryExists(string $relativePath): bool
    {
        try {
            $absolutePath = $this->getAbsolutePath($relativePath);
            return file_exists($absolutePath) && is_dir($absolutePath);
        } catch (InvalidPathException) {
            return false;
        }
    }

    /**
     * Overí, či má súbor správne oprávnenia.
     *
     * @param string $relativePath Relatívna cesta.
     * @param int $requiredPermissions Požadované oprávnenia (napr. 0644).
     * @return bool TRUE ak oprávnenia vyhovujú.
     */
    public function checkPermissions(string $relativePath, int $requiredPermissions): bool
    {
        if (!$this->fileExists($relativePath)) {
            return false;
        }

        $absolutePath = $this->getAbsolutePath($relativePath);
        $currentPermissions = fileperms($absolutePath) & 0777;

        return ($currentPermissions & $requiredPermissions) === $requiredPermissions;
    }

    /**
     * Získa MIME typ súboru.
     *
     * @param string $relativePath Relatívna cesta.
     * @return string|null MIME typ alebo null.
     */
    public function getMimeType(string $relativePath): ?string
    {
        if (!$this->fileExists($relativePath)) {
            return null;
        }

        $absolutePath = $this->getAbsolutePath($relativePath);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return null;
        }

        $mimeType = finfo_file($finfo, $absolutePath);

        return $mimeType ?: null;
    }

    /**
     * Získa príponu súboru.
     *
     * @param string $relativePath Relatívna cesta.
     * @return string Prípona (bez bodky).
     */
    public function getExtension(string $relativePath): string
    {
        return pathinfo($relativePath, PATHINFO_EXTENSION);
    }

    /**
     * Získa názov súboru bez prípony.
     *
     * @param string $relativePath Relatívna cesta.
     * @return string Názov súboru bez prípony.
     */
    public function getFilename(string $relativePath): string
    {
        return pathinfo($relativePath, PATHINFO_FILENAME);
    }

    /**
     * Získa adresár súboru.
     *
     * @param string $relativePath Relatívna cesta.
     * @return string Adresár.
     */
    public function getDirectory(string $relativePath): string
    {
        return pathinfo($relativePath, PATHINFO_DIRNAME);
    }
}
