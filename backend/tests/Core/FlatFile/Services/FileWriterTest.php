<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class FileWriterTest extends TestCase
{
    private FileWriter $fileWriter;
    private string $root;

    protected function setUp(): void
    {
        $structure = [
            'content' => [
                'pages' => [],
                'blog' => [],
            ],
        ];

        $root = vfsStream::setup('storage', null, $structure);
        $this->root = vfsStream::url('storage');

        $validator = new FileValidator($this->root . '/content');
        $this->fileWriter = new FileWriter($validator);
    }

    public function testWriteNewFile(): void
    {
        $content = "---\ntitle: New Page\n---\n# New Content";
        $this->fileWriter->write('pages/new.md', $content);

        $this->assertFileExists($this->root . '/content/pages/new.md');
        $this->assertEquals($content, file_get_contents($this->root . '/content/pages/new.md'));
    }

    public function testWriteBinaryPreservesRawBytes(): void
    {
        $binary = "\x89PNG\r\n\x1a\n" . random_bytes(16);
        $this->fileWriter->writeBinary('pages/binary.png', $binary);

        $stored = file_get_contents($this->root . '/content/pages/binary.png');
        $this->assertSame($binary, $stored);
    }

    public function testWriteExistingFileCreatesBackup(): void
    {
        // Vytvorenie existujúceho súboru
        $initialContent = "---\ntitle: Old\n---\n# Old Content";
        $filePath = $this->root . '/content/pages/backup-test.md';
        file_put_contents($filePath, $initialContent);

        // Overenie, že súbor existuje
        $this->assertFileExists($filePath);

        $newContent = "---\ntitle: New\n---\n# New Content";
        $this->fileWriter->write('pages/backup-test.md', $newContent, true);

        // Kontrola zálohy – použijeme glob a spočítame súbory
        $backupFiles = glob($this->root . '/content/pages/backup-test.md.backup.*');

        // Výpis pre debug (ak je potrebný)
        if (empty($backupFiles)) {
            // Skúsime hľadať všetky súbory v adresári
            $allFiles = scandir($this->root . '/content/pages/');
            $backupFiles = array_filter($allFiles, function ($file) {
                return strpos($file, 'backup-test.md.backup.') === 0;
            });
        }

        $this->assertGreaterThan(0, count($backupFiles), 'Záložný súbor nebol vytvorený');

        // Kontrola nového obsahu
        $this->assertEquals($newContent, file_get_contents($filePath));
    }

    public function testWriteInvalidPath(): void
    {
        $this->expectException(InvalidPathException::class);
        $this->fileWriter->write('../config/settings.json', 'test');
    }

    public function testDeleteMovesToTrash(): void
    {
        // Vytvorenie súboru
        $filePath = $this->root . '/content/pages/delete-me.md';
        file_put_contents($filePath, '# Test');

        $this->assertFileExists($filePath);

        $this->fileWriter->delete('pages/delete-me.md');

        $this->assertFileDoesNotExist($filePath);

        $trashDir = $this->root . '/content/trash';
        $this->assertDirectoryExists($trashDir);

        $trashFiles = scandir($trashDir);
        $trashFiles = array_filter($trashFiles, function ($file) {
            return $file !== '.' && $file !== '..';
        });

        $this->assertGreaterThan(0, count($trashFiles), 'Súbor nebol presunutý do koša');

        $found = false;
        $metaFound = false;
        foreach ($trashFiles as $file) {
            if (str_contains($file, 'delete-me') && str_ends_with($file, '.meta.json')) {
                $metaFound = true;
                $meta = json_decode((string) file_get_contents($trashDir . '/' . $file), true);
                $this->assertSame('pages/delete-me.md', $meta['originalPath'] ?? null);
                $this->assertArrayHasKey('id', $meta);
                $this->assertArrayHasKey('trashFilename', $meta);
            }
            if (str_contains($file, 'delete-me') && !str_ends_with($file, '.meta.json')) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'Súbor delete-me.md nebol nájdený v koši');
        $this->assertTrue($metaFound, 'Sidecar .meta.json musí existovať');
    }

    public function testDeletePermanently(): void
    {
        file_put_contents($this->root . '/content/pages/delete-permanent.md', '# Test');

        $this->fileWriter->delete('pages/delete-permanent.md', false);

        $this->assertFileDoesNotExist($this->root . '/content/pages/delete-permanent.md');

        $trashFiles = glob($this->root . '/content/trash/*_delete-permanent.md') ?: [];
        $this->assertCount(0, $trashFiles);
    }

    public function testCreateDirectory(): void
    {
        $this->fileWriter->createDirectory('pages/subdir');

        $this->assertDirectoryExists($this->root . '/content/pages/subdir');
    }

    public function testCopy(): void
    {
        file_put_contents($this->root . '/content/pages/source.md', '# Source');

        $this->fileWriter->copy('pages/source.md', 'pages/destination.md');

        $this->assertFileExists($this->root . '/content/pages/destination.md');
        $this->assertEquals('# Source', file_get_contents($this->root . '/content/pages/destination.md'));
    }

    public function testMove(): void
    {
        file_put_contents($this->root . '/content/pages/move-source.md', '# Move');

        $this->fileWriter->move('pages/move-source.md', 'pages/move-destination.md');

        $this->assertFileDoesNotExist($this->root . '/content/pages/move-source.md');
        $this->assertFileExists($this->root . '/content/pages/move-destination.md');
        $this->assertEquals('# Move', file_get_contents($this->root . '/content/pages/move-destination.md'));
    }

    public function testCopyNonExistentSource(): void
    {
        $this->expectException(FlatFileException::class);
        $this->fileWriter->copy('pages/non-existent.md', 'pages/destination.md');
    }
}
