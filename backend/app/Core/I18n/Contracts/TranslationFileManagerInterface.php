<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\I18n\Contracts;

/**
 * Read/write access to language catalog files (It.18d).
 */
interface TranslationFileManagerInterface
{
    /**
     * @return array{sources: list<array<string, mixed>>, files: list<array<string, mixed>>}
     */
    public function listCatalog(): array;

    public function canEdit(string $path): bool;

    public function readFile(string $path): string;

    public function writeFile(string $path, string $content): bool;

    /**
     * @return array<string, mixed>
     */
    public function getFileInfo(string $path): array;

    /**
     * @return list<string>
     */
    public function getBackups(string $path): array;

    public function restoreBackup(string $path, string $backupBasename): bool;

    /**
     * @return list<array{code: string, label: string, builtin?: bool}>
     */
    public function listLocales(): array;

    /**
     * @return array<string, mixed>
     */
    public function createLocale(string $code, string $label, string $copyFrom = 'sk'): array;

    /**
     * @return list<array{code: string, message: string, line?: int, hint?: string}>
     */
    public function validateContent(string $path, string $content): array;
}
