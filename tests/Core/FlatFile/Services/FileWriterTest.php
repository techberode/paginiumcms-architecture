<?php

declare(strict_types=1);

namespace tests\Core\FlatFile\Services\FileWriterTest.php;

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

    public function testWriteExistingFileCreatesBackup(): void
    {
        // Vytvorenie existujúceho súboru
        $initialContent = "---\ntitle: Old\n---\n# Old Content";
        file_put_contents($this->root . '/content/pages/backup-test.md', $initialContent);

        $newContent = "---\ntitle: New\n---\n# New Content";
        $this->fileWriter->write('pages/backup-test.md', $newContent, true);

        // Kontrola zálohy
        $files = glob($this->root . '/content/pages/backup-test.md.backup.*');
        $this->assertCount(1, $files);

        // Kontrola nového obsahu
        $this->assertEquals($newContent, file_get_contents($this->root . '/content/pages/backup-test.md'));
    }

    public function testWriteInvalidPath(): void
    {
        $this->expectException(InvalidPathException::class);
        $this->fileWriter->write('../config/settings.json', 'test');
    }

    public function testDeleteMovesToTrash(): void
    {
        // Vytvorenie súboru
        file_put_contents($this->root . '/content/pages/delete-me.md', '# Test');

        $this->fileWriter->delete('pages/delete-me.md');

        // Súbor by mal zmiznúť z pôvodného umiestnenia
        $this->assertFileDoesNotExist($this->root . '/content/pages/delete-me.md');

        // Mal by byť v koši
        $trashFiles = glob($this->root . '/content/trash/*_delete-me.md');
        $this->assertCount(1, $trashFiles);
    }

    public function testDeletePermanently(): void
    {
        // Vytvorenie súboru
        file_put_contents($this->root . '/content/pages/delete-permanent.md', '# Test');

        $this->fileWriter->delete('pages/delete-permanent.md', false);

        $this->assertFileDoesNotExist($this->root . '/content/pages/delete-permanent.md');

        // V koši by nemal byť
        $trashFiles = glob($this->root . '/content/trash/*_delete-permanent.md');
        $this->assertCount(0, $trashFiles);
    }

    public function testCreateDirectory(): void
    {
        $this->fileWriter->createDirectory('pages/subdir');

        $this->assertDirectoryExists($this->root . '/content/pages/subdir');
        $this->assertDirectoryIsReadable($this->root . '/content/pages/subdir');
    }

    public function testCopy(): void
    {
        // Vytvorenie zdrojového súboru
        file_put_contents($this->root . '/content/pages/source.md', '# Source');

        $this->fileWriter->copy('pages/source.md', 'pages/destination.md');

        $this->assertFileExists($this->root . '/content/pages/destination.md');
        $this->assertEquals('# Source', file_get_contents($this->root . '/content/pages/destination.md'));
    }

    public function testMove(): void
    {
        // Vytvorenie zdrojového súboru
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
