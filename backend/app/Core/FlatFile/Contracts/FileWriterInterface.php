<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Contracts;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;

/**
 * Rozhranie pre zápis súborov do FlatFile úložiska.
 *
 * Definuje metódy na bezpečný zápis, vytváranie adresárov a zálohovanie.
 */
interface FileWriterInterface
{
    /**
     * Zapíše obsah do súboru.
     *
     * @param string $relativePath Relatívna cesta k súboru.
     * @param string $content Obsah na zápis.
     * @param bool $createBackup Vytvoriť záložnú kópiu pred zápisom.
     * @throws FlatFileException Ak zápis zlyhá.
     * @throws InvalidPathException Ak cesta obsahuje zakázané znaky.
     */
    public function write(string $relativePath, string $content, bool $createBackup = true): void;

    /**
     * Vymaže súbor.
     *
     * @param string $relativePath Relatívna cesta k súboru.
     * @param bool $moveToTrash Presunúť do koša namiesto trvalého vymazania.
     * @throws FlatFileException Ak vymazanie zlyhá.
     */
    public function delete(string $relativePath, bool $moveToTrash = true): void;

    /**
     * Vytvorí adresár.
     *
     * @param string $relativePath Relatívna cesta k adresáru.
     * @param int $permissions Oprávnenia (napr. 0755).
     * @throws FlatFileException Ak vytvorenie zlyhá.
     */
    public function createDirectory(string $relativePath, int $permissions = 0755): void;

    /**
     * Skopíruje súbor alebo adresár.
     *
     * @param string $source Relatívna cesta zdroja.
     * @param string $destination Relatívna cesta cieľa.
     * @throws FlatFileException Ak kopírovanie zlyhá.
     */
    public function copy(string $source, string $destination): void;

    /**
     * Presunie súbor alebo adresár.
     *
     * @param string $source Relatívna cesta zdroja.
     * @param string $destination Relatívna cesta cieľa.
     * @throws FlatFileException Ak presun zlyhá.
     */
    public function move(string $source, string $destination): void;

    public function getBasePath(): string;
}

