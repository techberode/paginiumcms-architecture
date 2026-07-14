<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class FileValidatorTest extends TestCase
{
    private FileValidator $validator;
    private string $root;

    protected function setUp(): void
    {
        $structure = [
            'content' => [
                'pages' => [
                    'home.md' => '# Home',
                ],
                'blog' => [],
            ],
        ];

        $root = vfsStream::setup('storage', null, $structure);
        $this->root = vfsStream::url('storage');

        $this->validator = new FileValidator($this->root . '/content');
    }

    public function testValidatePathValid(): void
    {
        // Malo by prejsť bez výnimky
        $this->validator->validatePath('pages/home.md');
        $this->validator->validatePath('pages/subdir/file.md');
        $this->validator->validatePath('blog/2024-01-01-test.md');
        $this->validator->validatePath(''); // Prázdna cesta je povolená
        $this->validator->validatePath('.'); // Aktuálny adresár je povolený
        $this->addToAssertionCount(5);
    }

    public function testValidatePathThrowsOnEmpty(): void
    {
        // Tento test už neplatí – prázdna cesta je povolená
        // Preskočíme ho, alebo zmeníme očakávanie
        $this->markTestSkipped('Prázdna cesta je teraz povolená.');
    }

    public function testValidatePathThrowsOnPathTraversal(): void
    {
        $this->expectException(InvalidPathException::class);
        $this->validator->validatePath('../config/settings.json');
    }

    public function testValidatePathThrowsOnForbiddenCharacters(): void
    {
        $this->expectException(InvalidPathException::class);
        $this->validator->validatePath('pages/file<with>bad|chars?*.md');
    }

    public function testGetAbsolutePath(): void
    {
        $absolute = $this->validator->getAbsolutePath('pages/home.md');
        $this->assertStringContainsString('/content/pages/home.md', $absolute);
    }

    public function testGetAbsolutePathEmpty(): void
    {
        $absolute = $this->validator->getAbsolutePath('');
        $this->assertStringContainsString('/content', $absolute);
    }

    public function testFileExists(): void
    {
        $this->assertTrue($this->validator->fileExists('pages/home.md'));
        $this->assertFalse($this->validator->fileExists('pages/missing.md'));
    }

    public function testDirectoryExists(): void
    {
        $this->assertTrue($this->validator->directoryExists('pages'));
        $this->assertTrue($this->validator->directoryExists('blog'));
        $this->assertFalse($this->validator->directoryExists('missing'));
    }

    public function testGetMimeType(): void
    {
        $this->assertEquals('text/plain', $this->validator->getMimeType('pages/home.md'));
        $this->assertNull($this->validator->getMimeType('pages/missing.md'));
    }

    public function testGetExtension(): void
    {
        $this->assertEquals('md', $this->validator->getExtension('pages/home.md'));
        $this->assertEquals('json', $this->validator->getExtension('config/settings.json'));
        $this->assertEquals('', $this->validator->getExtension('pages/noextension'));
    }

    public function testGetFilename(): void
    {
        $this->assertEquals('home', $this->validator->getFilename('pages/home.md'));
        $this->assertEquals('settings', $this->validator->getFilename('config/settings.json'));
    }

    public function testGetDirectory(): void
    {
        $this->assertEquals('pages', $this->validator->getDirectory('pages/home.md'));
        $this->assertEquals('config', $this->validator->getDirectory('config/settings.json'));
        $this->assertEquals('.', $this->validator->getDirectory('file.md'));
    }
}
