<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use function utf8_normalize;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Exception\FileNotFoundException;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;

class FileReader implements FileReaderInterface
{
    private FileValidator $validator;

    public function __construct(FileValidator $validator)
    {
        $this->validator = $validator;
    }

    public function read(string $relativePath): string
    {
        $absolutePath = $this->validator->getAbsolutePath($relativePath);

        if (!file_exists($absolutePath) || !is_file($absolutePath)) {
            throw new FileNotFoundException($relativePath);
        }

        if (!is_readable($absolutePath)) {
            throw new FlatFileException(sprintf('Súbor nie je čitateľný: %s', $relativePath));
        }

        $content = file_get_contents($absolutePath);

        if ($content === false) {
            throw new FlatFileException(sprintf('Nepodarilo sa načítať súbor: %s', $relativePath));
        }

        return utf8_normalize($content);
    }

    public function exists(string $relativePath): bool
    {
        return $this->validator->fileExists($relativePath);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getInfo(string $relativePath): array
    {
        $absolutePath = $this->validator->getAbsolutePath($relativePath);

        if (!file_exists($absolutePath) || !is_file($absolutePath)) {
            throw new FileNotFoundException($relativePath);
        }

        $stat = stat($absolutePath);

        if ($stat === false) {
            throw new FlatFileException(sprintf('Nepodarilo sa získať informácie o súbore: %s', $relativePath));
        }

        return [
            'size' => $stat['size'],
            'mtime' => $stat['mtime'],
            'is_readable' => is_readable($absolutePath),
            'is_writable' => is_writable($absolutePath),
        ];
    }


    /**
     * Získa základnú cestu k úložisku.
     *
     * @return string Základná cesta.
     */
    public function getBasePath(): string
    {
        return $this->validator->getBasePath();
    }


    /**
     * @return array<int|string, mixed>
     */
    public function listFiles(string $relativePath, string $pattern = '*'): array
    {
        // Ak je cesta prázdna alebo '.', použijeme prázdny reťazec
        if (empty($relativePath) || $relativePath === '.') {
            $relativePath = '';
        }

        // Získame absolútnu cestu
        try {
            $absolutePath = $this->validator->getAbsolutePath($relativePath);
        } catch (InvalidPathException $e) {
            if ($relativePath === '') {
                $absolutePath = $this->validator->getAbsolutePath('.');
            } else {
                throw $e;
            }
        }

        if (!file_exists($absolutePath) || !is_dir($absolutePath)) {
            throw new FileNotFoundException($relativePath ?: '.');
        }

        if (!is_readable($absolutePath)) {
            throw new FlatFileException(sprintf('Adresár nie je čitateľný: %s', $relativePath ?: '.'));
        }

        // Použijeme scandir namiesto glob pre lepšiu kompatibilitu s vfsStream
        $files = scandir($absolutePath);

        if ($files === false) {
            throw new FlatFileException(sprintf('Nepodarilo sa načítať zoznam súborov: %s', $relativePath ?: '.'));
        }

        // Odstránime . a ..
        $files = array_filter($files, function ($file) {
            return $file !== '.' && $file !== '..';
        });

        // Aplikujeme pattern filter
        if ($pattern !== '*') {
            // Konvertujeme pattern na regulárny výraz
            $regex = '/^' . str_replace(['*', '?'], ['.*', '.'], $pattern) . '$/';
            $files = array_filter($files, function ($file) use ($regex) {
                return preg_match($regex, $file) === 1;
            });
        }

        // Získame základnú cestu pre konverziu na relatívne cesty
        $basePath = $this->validator->getAbsolutePath('');
        $basePathLength = strlen($basePath) + 1;

        // Konvertujeme na relatívne cesty
        $result = array_map(function ($file) use ($absolutePath, $basePathLength) {
            $fullPath = $absolutePath . '/' . $file;
            // Ak je súbor v podadresári, zachováme relatívnu cestu
            if (strpos($fullPath, $this->validator->getAbsolutePath('')) === 0) {
                return substr($fullPath, $basePathLength);
            }
            return $file;
        }, array_values($files));

        // Zotriedime výsledky
        sort($result);

        return $result;
    }
}
