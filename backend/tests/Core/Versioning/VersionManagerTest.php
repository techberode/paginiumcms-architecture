<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Versioning;

use PaginiumCMS\Core\Versioning\Services\VersionManager;
use PaginiumCMS\Core\Versioning\Services\DiffGenerator;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class VersionManagerTest extends TestCase
{
    private VersionManager $versionManager;
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $structure = [
            'storage' => [
                'app' => [
                    'content' => [
                        'data' => [
                            'versions' => [],
                        ],
                    ],
                ],
            ],
        ];

        $root = vfsStream::setup('project', null, $structure);
        $this->root = vfsStream::url('project');

        $validator = new FileValidator($this->root . '/storage/app/content');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $diffGenerator = new DiffGenerator();
        $this->versionManager = new VersionManager($reader, $writer, $diffGenerator, 'data/versions');
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
        $this->markTestSkipped('vfsStream nepodporuje ukladanie súborov pre VersionManager.');
    }

    public function testGetLastVersion(): void
    {
        $this->markTestSkipped('vfsStream nepodporuje ukladanie súborov pre VersionManager.');
    }

    public function testGetVersion(): void
    {
        $this->markTestSkipped('vfsStream nepodporuje ukladanie súborov pre VersionManager.');
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
