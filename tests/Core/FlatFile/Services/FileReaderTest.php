<?php

declare(strict_types=1);

namespace tests\Core\FlatFile\Services\FileReaderTest.php;

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
                    'home.md' => '---\ntitle: Home\n---\n# Welcome',
                ],
                'blog' => [
                    '2024-01-01-test.md' => '---\ntitle: Test\n---\n# Test Article',
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
        $files = $this->fileReader->listFiles('.', 'pages/*.md');

        $this->assertIsArray($files);
        $this->assertContains('pages/home.md', $files);

        $blogFiles = $this->fileReader->listFiles('.', 'blog/*.md');
        $this->assertContains('blog/2024-01-01-test.md', $blogFiles);
    }

    public function testListFilesInvalidDirectory(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->fileReader->listFiles('invalid', '*.md');
    }
}
