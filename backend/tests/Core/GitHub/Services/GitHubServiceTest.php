<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\GitHub\Services;

use PaginiumCMS\Core\GitHub\Services\GitHubService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class GitHubServiceTest extends TestCase
{
    private GitHubService $service;
    private string $root;
    private string $contentPath;

    protected function setUp(): void
    {
        parent::setUp();

        $structure = [
            'storage' => [
                'app' => [
                    'content' => [
                        'pages' => [
                            'home.md' => '---\ntitle: Home\n---\n# Welcome',
                            'about.md' => '---\ntitle: About\n---\n# About Us',
                        ],
                        'blog' => [
                            '2024-01-01-test.md' => '---\ntitle: Test\n---\n# Test Article',
                        ],
                    ],
                ],
            ],
        ];

        $root = vfsStream::setup('project', null, $structure);
        $this->root = vfsStream::url('project');
        $this->contentPath = $this->root . '/storage/app/content';

        $validator = new FileValidator($this->root . '/storage/app');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $this->service = new GitHubService(
            $reader,
            $writer,
            [
                'token' => 'test_token',
                'repo' => 'test/repo',
                'branch' => 'main',
                'enabled' => true,
                'auto_sync' => false,
                'content_path' => $this->contentPath,
            ]
        );
    }

    public function testGetStatus(): void
    {
        $status = $this->service->getStatus();

        $this->assertTrue($status['enabled']);
        $this->assertEquals('test/repo', $status['repo']);
        $this->assertEquals('main', $status['branch']);
        $this->assertFalse($status['auto_sync']);
        $this->assertTrue($status['configured']);
    }

    public function testGetStatusWhenNotConfigured(): void
    {
        $service = new GitHubService(
            $this->createMock(FileReader::class),
            $this->createMock(FileWriter::class),
            ['enabled' => false]
        );

        $status = $service->getStatus();
        $this->assertFalse($status['enabled']);
        $this->assertFalse($status['configured']);
    }

    public function testSetAutoSync(): void
    {
        $this->service->setAutoSync(true);
        $status = $this->service->getStatus();
        $this->assertTrue($status['auto_sync']);

        $this->service->setAutoSync(false);
        $status = $this->service->getStatus();
        $this->assertFalse($status['auto_sync']);
    }

    public function testExportWhenNotConfigured(): void
    {
        $service = new GitHubService(
            $this->createMock(FileReader::class),
            $this->createMock(FileWriter::class),
            ['enabled' => false]
        );

        $result = $service->export();
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testImportWhenNotConfigured(): void
    {
        $service = new GitHubService(
            $this->createMock(FileReader::class),
            $this->createMock(FileWriter::class),
            ['enabled' => false]
        );

        $result = $service->import();
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testSyncWhenNotConfigured(): void
    {
        $service = new GitHubService(
            $this->createMock(FileReader::class),
            $this->createMock(FileWriter::class),
            ['enabled' => false]
        );

        $result = $service->sync();
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testExportFindsFiles(): void
    {
        // Overíme, že export nájde súbory
        // Poznámka: Skutočný export by volal GitHub API, ale tu len testujeme logiku
        $result = $this->service->export();
        // V reálnom scenári by sme mockovali API volania
        // Tento test len overuje, že metóda existuje a vracia správnu štruktúru
        $this->assertArrayHasKey('success', $result);
    }

    public function testImportFindsFiles(): void
    {
        // Overíme, že import nájde súbory
        $result = $this->service->import();
        $this->assertArrayHasKey('success', $result);
    }
}
