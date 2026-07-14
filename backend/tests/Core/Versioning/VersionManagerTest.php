<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Versioning;

use PaginiumCMS\Core\Versioning\Services\VersionManager;
use PaginiumCMS\Core\Versioning\Services\DiffGenerator;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PHPUnit\Framework\TestCase;

class VersionManagerTest extends TestCase
{
    private VersionManager $versionManager;
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        // REÁLNY dočasný adresár namiesto vfsStream – VersionManager používa glob()
        // a file_get_contents(), ktoré vfsStream nepodporuje. Vďaka tomu môžu byť
        // testy čítania verzií reálne spustené (predtým markTestSkipped).
        $this->root = sys_get_temp_dir() . '/pag_versions_test_' . uniqid();
        mkdir($this->root . '/storage/app/content/data/versions', 0777, true);

        $validator = new FileValidator($this->root . '/storage/app/content');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $diffGenerator = new DiffGenerator();
        $this->versionManager = new VersionManager($reader, $writer, $diffGenerator, 'data/versions');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->root);
        parent::tearDown();
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testCreateVersion(): void
    {
        $version = $this->versionManager->createVersion(
            'page_1',
            'page',
            '# Hello World',
            '{"title":"Hello"}',
            'user_1',
            'Initial commit'
        );

        $this->assertNotEmpty($version->getId());
        $this->assertEquals(1, $version->getVersion());
        $this->assertEquals('page_1', $version->getContentId());
        $this->assertEquals('user_1', $version->getCreatedBy());
        $this->assertEquals('Initial commit', $version->getMessage());
    }

    public function testGetVersions(): void
    {
        $this->versionManager->createVersion('page_1', 'page', '# v1', '{"title":"v1"}', 'user_1', 'prvá');
        $this->versionManager->createVersion('page_1', 'page', '# v2', '{"title":"v2"}', 'user_1', 'druhá');

        $versions = $this->versionManager->getVersions('page_1');

        $this->assertCount(2, $versions);
        // Zoradené zostupne – najnovšia (v2) je prvá.
        $this->assertSame(2, $versions[0]->getVersion());
        $this->assertSame(1, $versions[1]->getVersion());
    }

    public function testGetLastVersion(): void
    {
        $this->versionManager->createVersion('page_2', 'page', '# v1', '{}', 'user_1', 'prvá');
        $this->versionManager->createVersion('page_2', 'page', '# v2', '{}', 'user_1', 'druhá');

        $last = $this->versionManager->getLastVersion('page_2');

        $this->assertNotNull($last);
        $this->assertSame(2, $last->getVersion());
        $this->assertSame('# v2', $last->getContent());
    }

    public function testGetVersion(): void
    {
        $this->versionManager->createVersion('page_3', 'page', '# Obsah verzie', '{"title":"T"}', 'autor', 'správa');

        $version = $this->versionManager->getVersion('page_3', 1);

        $this->assertNotNull($version);
        $this->assertSame(1, $version->getVersion());
        $this->assertSame('# Obsah verzie', $version->getContent());
        $this->assertSame('page_3', $version->getContentId());
        $this->assertSame('autor', $version->getCreatedBy());
        $this->assertSame('správa', $version->getMessage());
    }

    public function testGetVersionReturnsNullForMissing(): void
    {
        $this->assertNull($this->versionManager->getVersion('neexistuje', 99));
    }

    public function testDiffGenerator(): void
    {
        $diffGenerator = new DiffGenerator();
        $old = "Line 1\nLine 2\nLine 3";
        $new = "Line 1\nLine 2 modified\nLine 3\nLine 4";

        $diff = $diffGenerator->generate($old, $new);

        $this->assertArrayHasKey('lines', $diff);
        $this->assertArrayHasKey('summary', $diff);
        $this->assertArrayHasKey('additions', $diff);
        $this->assertArrayHasKey('deletions', $diff);
    }
}
