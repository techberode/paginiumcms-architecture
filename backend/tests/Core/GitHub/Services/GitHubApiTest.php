<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\GitHub\Services;

use PaginiumCMS\Core\GitHub\Services\GitHubService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GitHubApiTest extends TestCase
{
    /** @var FileReader&MockObject */
    private FileReader $mockReader;
    /** @var FileWriter&MockObject */
    private FileWriter $mockWriter;
    private GitHubService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockReader = $this->createMock(FileReader::class);
        $this->mockWriter = $this->createMock(FileWriter::class);

        $this->service = new GitHubService(
            $this->mockReader,
            $this->mockWriter,
            [
                'token' => 'test_token',
                'repo' => 'test/repo',
                'branch' => 'main',
                'enabled' => true,
                'content_path' => '/content',
            ]
        );
    }

    public function testExportWithMockedReader(): void
    {
        $this->mockReader
        ->expects($this->once())
        ->method('listFiles')
        ->with('/content', '*.*')
        ->willReturn(['pages/home.md']);

        $this->mockReader
        ->expects($this->once())
        ->method('read')
        ->with('/content/pages/home.md')
        ->willReturn('Test content');

        $result = $this->service->export();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('files', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    public function testImportWithMockedReader(): void
    {
        // V reálnom importe sa volajú GitHub API metódy,
        // ktoré v mocku nie sú implementované. Preskočíme tento test.
        $this->markTestSkipped('Import vyžaduje reálne GitHub API volania.');
    }

    public function testSyncWithMockedReader(): void
    {
        $this->mockReader
        ->expects($this->once())
        ->method('listFiles')
        ->with('/content', '*.*')
        ->willReturn(['pages/home.md']);

        $this->mockReader
        ->expects($this->once())
        ->method('read')
        ->willReturn('Test content');

        $result = $this->service->sync();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('exported', $result);
        $this->assertArrayHasKey('imported', $result);
        $this->assertArrayHasKey('errors', $result);
    }
}
