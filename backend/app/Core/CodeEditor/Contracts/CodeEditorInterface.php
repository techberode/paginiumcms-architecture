<?php
// backend/app/Core/CodeEditor/Contracts/CodeEditorInterface.php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Contracts;

interface CodeEditorInterface
{
    public function canEdit(string $path): bool;
    public function readFile(string $path): string;
    public function writeFile(string $path, string $content): bool;
    /**
     * @return array<int|string, mixed>
     */
    public function listFiles(string $directory): array;
    /**
     * @return array<int|string, mixed>
     */
    public function getFileInfo(string $path): array;
}
