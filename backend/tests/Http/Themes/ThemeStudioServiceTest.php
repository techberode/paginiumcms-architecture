<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Themes;

use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Http\Themes\Services\ThemeStudioService;
use PHPUnit\Framework\TestCase;

final class ThemeStudioServiceTest extends TestCase
{
    private string $baseDir;
    private string $themesRoot;
    private ThemeStudioService $studio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_theme_studio_' . uniqid('', true);
        $this->themesRoot = $this->baseDir . '/themes';
        mkdir($this->themesRoot . '/sample-theme/templates', 0777, true);
        mkdir($this->themesRoot . '/sample-theme/assets', 0777, true);

        file_put_contents($this->themesRoot . '/sample-theme/theme.json', '{"id":"sample-theme"}');
        file_put_contents($this->themesRoot . '/sample-theme/templates/default.html', '<main>{{content}}</main>');
        file_put_contents($this->themesRoot . '/sample-theme/assets/theme.css', 'body{color:#111}');
        file_put_contents($this->themesRoot . '/sample-theme/preview.png', 'not-text');
        file_put_contents($this->themesRoot . '/sample-theme/.hidden.html', '<p>nope</p>');

        $this->studio = new ThemeStudioService($this->themesRoot);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testListFilesReturnsAllowListedTextFilesOnly(): void
    {
        $files = $this->studio->listFiles('sample-theme');
        $paths = array_map(static fn (array $item): string => $item['relativePath'], $files);

        $this->assertContains('theme.json', $paths);
        $this->assertContains('templates/default.html', $paths);
        $this->assertContains('assets/theme.css', $paths);
        $this->assertNotContains('preview.png', $paths);
        $this->assertNotContains('.hidden.html', $paths);
    }

    public function testReadFileReturnsContentAndLanguage(): void
    {
        $file = $this->studio->readFile('sample-theme', 'templates/default.html');

        $this->assertSame('templates/default.html', $file['relativePath']);
        $this->assertSame('<main>{{content}}</main>', $file['content']);
        $this->assertSame('html', $file['language']);
        $this->assertSame('html', $file['tab']);
    }

    public function testReadFileRejectsPathTraversal(): void
    {
        $this->expectException(ThemeStudioException::class);
        $this->expectExceptionMessage('Invalid theme file path.');
        $this->studio->readFile('sample-theme', '../theme.json');
    }

    public function testReadFileRejectsAbsolutePath(): void
    {
        $this->expectException(ThemeStudioException::class);
        $this->studio->readFile('sample-theme', '/etc/passwd');
    }

    public function testReadFileRejectsDisallowedExtension(): void
    {
        $this->expectException(ThemeStudioException::class);
        $this->expectExceptionMessage('Theme file type is not allowed.');
        $this->studio->readFile('sample-theme', 'preview.png');
    }

    public function testMissingThemeReturnsNotFound(): void
    {
        try {
            $this->studio->listFiles('missing-theme');
            $this->fail('Expected ThemeStudioException');
        } catch (ThemeStudioException $exception) {
            $this->assertSame(404, $exception->httpStatus());
        }
    }

    public function testInvalidThemeIdIsRejected(): void
    {
        try {
            $this->studio->listFiles('../sample-theme');
            $this->fail('Expected ThemeStudioException');
        } catch (ThemeStudioException $exception) {
            $this->assertSame(400, $exception->httpStatus());
        }
    }

    public function testReadMissingFileReturnsNotFound(): void
    {
        try {
            $this->studio->readFile('sample-theme', 'templates/missing.html');
            $this->fail('Expected ThemeStudioException');
        } catch (ThemeStudioException $exception) {
            $this->assertSame(404, $exception->httpStatus());
        }
    }

    public function testReadFileRejectsOversizedContent(): void
    {
        $huge = str_repeat('x', ThemeStudioService::MAX_FILE_BYTES + 1);
        file_put_contents($this->themesRoot . '/sample-theme/templates/huge.html', $huge);

        try {
            $this->studio->readFile('sample-theme', 'templates/huge.html');
            $this->fail('Expected ThemeStudioException');
        } catch (ThemeStudioException $exception) {
            $this->assertSame(413, $exception->httpStatus());
        }
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
