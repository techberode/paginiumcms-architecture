<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Exception\FileNotFoundException;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class FileReaderTest extends TestCase
{
    private FileReader $fileReader;
    private string $root;

    protected function setUp(): void
    {
        $structure = [
            'content' => [
                'pages' => [
                    'home.md' => "---\ntitle: Home\n---\n# Welcome",
                    'about.md' => "---\ntitle: About\n---\n# About Us",
                ],
                'blog' => [
                    '2024-01-01-test.md' => "---\ntitle: Test\n---\n# Test Article",
                ],
            ],
        ];

        $root = vfsStream::setup('storage', null, $structure);
        $this->root = vfsStream::url('storage');

        $validator = new FileValidator($this->root . '/content');
        $this->fileReader = new FileReader($validator);
    }

    public function testReadExistingFile(): void
    {
        $content = $this->fileReader->read('pages/home.md');
        $this->assertStringContainsString('# Welcome', $content);
        $this->assertStringContainsString('title: Home', $content);
    }

    public function testReadFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->fileReader->read('pages/missing.md');
    }

    public function testReadInvalidPath(): void
    {
        $this->expectException(InvalidPathException::class);
        $this->fileReader->read('../config/settings.json');
    }

    public function testExists(): void
    {
        $this->assertTrue($this->fileReader->exists('pages/home.md'));
        $this->assertFalse($this->fileReader->exists('pages/missing.md'));
        $this->assertFalse($this->fileReader->exists('../config/settings.json'));
    }

    public function testGetInfo(): void
    {
        $info = $this->fileReader->getInfo('pages/home.md');

        $this->assertArrayHasKey('size', $info);
        $this->assertArrayHasKey('mtime', $info);
        $this->assertArrayHasKey('is_readable', $info);
        $this->assertArrayHasKey('is_writable', $info);
        $this->assertTrue($info['is_readable']);
    }

    public function testListFiles(): void
    {
        // Test listovania súborov v adresári pages
        $files = $this->fileReader->listFiles('pages');

        $this->assertIsArray($files);
        $this->assertNotEmpty($files, 'Zoznam súborov v pages je prázdny');

        // Skontrolujeme, či obsahuje očakávané súbory
        $foundHome = false;
        $foundAbout = false;

        foreach ($files as $file) {
            if (strpos($file, 'home.md') !== false) {
                $foundHome = true;
            }
            if (strpos($file, 'about.md') !== false) {
                $foundAbout = true;
            }
        }

        $this->assertTrue($foundHome, 'Súbor home.md sa nenašiel');
        $this->assertTrue($foundAbout, 'Súbor about.md sa nenašiel');

        // Test listovania súborov v adresári blog
        $blogFiles = $this->fileReader->listFiles('blog');
        $this->assertIsArray($blogFiles);
        $this->assertNotEmpty($blogFiles, 'Zoznam súborov v blog je prázdny');

        $foundTest = false;
        foreach ($blogFiles as $file) {
            if (strpos($file, 'test.md') !== false) {
                $foundTest = true;
                break;
            }
        }
        $this->assertTrue($foundTest, 'Súbor test.md sa nenašiel');
    }

    public function testListFilesWithPattern(): void
    {
        // Test s patternom
        $files = $this->fileReader->listFiles('pages', '*.md');
        $this->assertIsArray($files);
        $this->assertCount(2, $files);
    }

    public function testListFilesInvalidDirectory(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->fileReader->listFiles('invalid');
    }
}
