<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Backup\Services;

use PaginiumCMS\Core\Backup\Services\BackupManager;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class BackupManagerTest extends TestCase
{
    private BackupManager $backupManager;
    private string $root;
    private string $backupPath;
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
                        ],
                    ],
                ],
                'backups' => [],
            ],
        ];

        $root = vfsStream::setup('project', null, $structure);
        $this->root = vfsStream::url('project');

        $this->backupPath = $this->root . '/storage/backups';
        $this->contentPath = $this->root . '/storage/app/content';

        $validator = new FileValidator($this->root . '/storage/app');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $this->backupManager = new BackupManager(
            $reader,
            $writer,
            $this->backupPath,
            $this->contentPath
        );
    }

    public function testCreateBackup(): void
    {
        $this->markTestSkipped('ZipArchive nefunguje v vfsStream.');
    }

    public function testListBackups(): void
    {
        $this->markTestSkipped('Vyžaduje funkčný createBackup.');
    }

    public function testGetBackup(): void
    {
        $this->markTestSkipped('Vyžaduje funkčný createBackup.');
    }

    public function testGetNonExistentBackup(): void
    {
        $found = $this->backupManager->getBackup('non_existent_id');
        $this->assertNull($found);
    }

    public function testDeleteBackup(): void
    {
        $this->markTestSkipped('Vyžaduje funkčný createBackup.');
    }

    public function testDeleteNonExistentBackup(): void
    {
        $result = $this->backupManager->deleteBackup('non_existent_id');
        $this->assertFalse($result);
    }

    public function testExportBackup(): void
    {
        $this->markTestSkipped('Vyžaduje funkčný createBackup.');
    }

    public function testExportNonExistentBackup(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->backupManager->exportBackup('non_existent_id');
    }

    public function testRestoreBackup(): void
    {
        $this->markTestSkipped('Vyžaduje funkčný createBackup.');
    }

    public function testRestoreFromFile(): void
    {
        $this->markTestSkipped('Vyžaduje funkčný createBackup.');
    }

    public function testRestoreNonExistentBackup(): void
    {
        $result = $this->backupManager->restore('non_existent_id');
        $this->assertFalse($result);
    }

    public function testImportBackup(): void
    {
        $this->markTestSkipped('Vyžaduje reálny ZIP súbor.');
    }

    public function testImportInvalidBackup(): void
    {
        $invalidPath = $this->backupPath . '/invalid.zip';
        file_put_contents($invalidPath, 'invalid content');

        $this->expectException(\RuntimeException::class);
        $this->backupManager->importBackup($invalidPath);
    }

    public function testScheduleBackup(): void
    {
        $this->backupManager->scheduleBackup('daily', 7);

        $schedule = $this->backupManager->getScheduleInfo();
        $this->assertEquals('daily', $schedule['interval']);
        $this->assertEquals(7, $schedule['keep']);
    }

    public function testGetScheduleInfo(): void
    {
        $schedule = $this->backupManager->getScheduleInfo();
        $this->assertArrayHasKey('enabled', $schedule);
        $this->assertFalse($schedule['enabled']);

        $this->backupManager->scheduleBackup('weekly', 14);
        $schedule = $this->backupManager->getScheduleInfo();
        $this->assertEquals('weekly', $schedule['interval']);
        $this->assertEquals(14, $schedule['keep']);
    }
}
