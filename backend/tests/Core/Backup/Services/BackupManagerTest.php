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

        $validator = new FileValidator($this->contentPath);
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
        $this->assertTrue($schedule['enabled']);
    }

    public function testClearScheduleRemovesPlan(): void
    {
        $this->backupManager->scheduleBackup('daily', 7);
        $this->backupManager->clearSchedule();

        $schedule = $this->backupManager->getScheduleInfo();
        $this->assertFalse($schedule['enabled']);
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
        $this->assertTrue($schedule['enabled']);
    }

    public function testRunScheduledBackupIfDueWithoutSchedule(): void
    {
        $result = $this->backupManager->runScheduledBackupIfDue();

        $this->assertFalse($result['ran']);
        $this->assertSame('no_schedule', $result['reason'] ?? null);
    }

    public function testRunScheduledBackupIfDueWhenNotYetDue(): void
    {
        $this->backupManager->scheduleBackup('daily', 7);

        $result = $this->backupManager->runScheduledBackupIfDue();

        $this->assertFalse($result['ran']);
        $this->assertSame('not_due', $result['reason'] ?? null);
    }

    public function testCreateAndRestoreRoundTripIncludesPages(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is required.');
        }

        $tempRoot = sys_get_temp_dir() . '/paginium_backup_rt_' . uniqid('', true);
        $backupPath = $tempRoot . '/backups';
        $contentPath = $tempRoot . '/content';
        mkdir($backupPath, 0755, true);
        mkdir($contentPath . '/pages', 0755, true);
        mkdir($contentPath . '/blog', 0755, true);
        mkdir($contentPath . '/data', 0755, true);
        file_put_contents($contentPath . '/pages/home.md', "---\ntitle: Home\n---\n# Home");
        file_put_contents($contentPath . '/blog/post.md', "---\ntitle: Post\n---\n# Post");
        file_put_contents($contentPath . '/data/settings.json', '{"general":{"siteName":"Test"}}');

        $validator = new FileValidator($contentPath);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $manager = new BackupManager($reader, $writer, $backupPath, $contentPath);

        try {
            $backup = $manager->create('roundtrip', ['includes' => ['content', 'config', 'data']]);
            $this->assertSame('completed', $backup->getStatus());

            unlink($contentPath . '/pages/home.md');
            unlink($contentPath . '/blog/post.md');
            unlink($contentPath . '/data/settings.json');

            $this->assertTrue($manager->restore($backup->getId()));
            $this->assertFileExists($contentPath . '/pages/home.md');
            $this->assertFileExists($contentPath . '/blog/post.md');
            $this->assertFileExists($contentPath . '/data/settings.json');
            $this->assertDirectoryDoesNotExist($contentPath . '/content');
        } finally {
            $this->removeDirectory($tempRoot);
        }
    }

    public function testCreateAndRestoreAfterSoftDeleteToTrash(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is required.');
        }

        $tempRoot = sys_get_temp_dir() . '/paginium_backup_trash_' . uniqid('', true);
        $backupPath = $tempRoot . '/backups';
        $contentPath = $tempRoot . '/content';
        mkdir($backupPath, 0755, true);
        mkdir($contentPath . '/blog', 0755, true);
        file_put_contents($contentPath . '/blog/deleted-post.md', "---\ntitle: Deleted\n---\n# Deleted");

        $validator = new FileValidator($contentPath);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $manager = new BackupManager($reader, $writer, $backupPath, $contentPath);

        try {
            $backup = $manager->create('before-delete', ['includes' => ['content']]);
            $writer->delete('blog/deleted-post.md', true);
            $this->assertFileDoesNotExist($contentPath . '/blog/deleted-post.md');

            $this->assertTrue($manager->restore($backup->getId()));
            $this->assertFileExists($contentPath . '/blog/deleted-post.md');
            $this->assertDirectoryDoesNotExist($contentPath . '/content');
        } finally {
            $this->removeDirectory($tempRoot);
        }
    }

    public function testRestoreLegacyDataOnlyBackup(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is required.');
        }

        $tempRoot = sys_get_temp_dir() . '/paginium_backup_legacy_' . uniqid('', true);
        $backupPath = $tempRoot . '/backups';
        $contentPath = $tempRoot . '/content';
        mkdir($backupPath, 0755, true);
        mkdir($contentPath . '/data', 0755, true);
        file_put_contents($contentPath . '/data/settings.json', '{"general":{"siteName":"Legacy"}}');

        $zipPath = $backupPath . '/legacy.zip';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $zip->addFromString('data/settings.json', '{"general":{"siteName":"Legacy"}}');
        $zip->close();

        unlink($contentPath . '/data/settings.json');

        $validator = new FileValidator($contentPath);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $manager = new BackupManager($reader, $writer, $backupPath, $contentPath);

        try {
            $this->assertTrue($manager->importBackup($zipPath));
            $this->assertFileExists($contentPath . '/data/settings.json');
            $this->assertDirectoryDoesNotExist($contentPath . '/content');
        } finally {
            $this->removeDirectory($tempRoot);
        }
    }

    public function testRunScheduledBackupIfDueWhenPastDue(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is required.');
        }

        $tempRoot = sys_get_temp_dir() . '/paginium_backup_' . uniqid('', true);
        $backupPath = $tempRoot . '/backups';
        $contentPath = $tempRoot . '/content';
        mkdir($backupPath, 0755, true);
        mkdir($contentPath . '/pages', 0755, true);
        file_put_contents($contentPath . '/pages/home.md', "---\ntitle: Home\n---\n# Home");

        $validator = new FileValidator($contentPath);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $manager = new BackupManager($reader, $writer, $backupPath, $contentPath);

        try {
            $manager->scheduleBackup('daily', 7);

            $schedulePath = $backupPath . '/schedule.json';
            $schedule = json_decode((string) file_get_contents($schedulePath), true);
            $schedule['next_run'] = date('Y-m-d H:i:s', time() - 3600);
            file_put_contents($schedulePath, json_encode($schedule, JSON_PRETTY_PRINT));

            $result = $manager->runScheduledBackupIfDue();

            $this->assertTrue($result['ran']);
            $this->assertArrayHasKey('backup', $result);
        } finally {
            $this->removeDirectory($tempRoot);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
