<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Contracts;

use PaginiumCMS\Core\FlatFile\Exception\FileNotFoundException;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;

/**
 * Rozhranie pre čítanie súborov z FlatFile úložiska.
 *
 * Definuje metódy na bezpečné čítanie súborov s kontrolou prístupu.
 */
interface FileReaderInterface
{
    /**
     * Načíta obsah súboru.
     *
     * @param string $relativePath Relatívna cesta k súboru (napr. 'pages/home.md').
     * @return string Obsah súboru.
     * @throws FileNotFoundException Ak súbor neexistuje.
     * @throws InvalidPathException Ak cesta obsahuje zakázané znaky (path traversal).
     */
    public function read(string $relativePath): string;

    /**
     * Načíta binárny obsah súboru bez UTF-8 normalizácie.
     *
     * @param string $relativePath Relatívna cesta k súboru.
     * @return string Binárny obsah súboru.
     * @throws FileNotFoundException Ak súbor neexistuje.
     */
    public function readBinary(string $relativePath): string;

    /**
     * Zistí, či súbor existuje.
     *
     * @param string $relativePath Relatívna cesta k súboru.
     * @return bool TRUE ak súbor existuje, inak FALSE.
     */
    public function exists(string $relativePath): bool;

    /**
     * Získa informácie o súbore.
     *
     * @param string $relativePath Relatívna cesta k súboru.
     * @return array{size: int, mtime: int, is_readable: bool, is_writable: bool}
     * @throws FileNotFoundException Ak súbor neexistuje.
 * @return array<int|string, mixed>
 */public function getInfo(string $relativePath): array;

    /**
     * Získa zoznam súborov v adresári.
     *
     * @param string $relativePath Relatívna cesta k adresáru.
     * @param string $pattern Voliteľný filter (napr. '*.md').
     * @return array<int, string> Zoznam súborov.
     * @throws InvalidPathException Ak cesta obsahuje zakázané znaky.
 */public function listFiles(string $relativePath, string $pattern = '*'): array;

    /**
     * Získa základnú cestu k úložisku.
     *
     * @return string Základná cesta.
     */
    public function getBasePath(): string;
}
