<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Logging;

use PaginiumCMS\Core\Logging\LogStoragePaths;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Logging\Services\ApplicationLogReader;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;

final class ApplicationLogReaderTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/paginium-log-reader-' . uniqid('', true);
        mkdir($this->baseDir . '/app', 0777, true);
        mkdir($this->baseDir . '/audit', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->baseDir);
        parent::tearDown();
    }

    public function testReadsAppLogsFromConfiguredDirectory(): void
    {
        $file = $this->baseDir . '/app/' . date('Y-m-d') . '.json';
        file_put_contents($file, JsonHelper::encode([
            [
                'id' => 'log_test',
                'timestamp' => date('Y-m-d H:i:s'),
                'severity' => LogSeverity::INFO,
                'category' => 'app',
                'message' => 'Test log entry',
            ],
        ]));

        $reader = new ApplicationLogReader([
            'app' => $this->baseDir . '/app',
        ]);

        $items = $reader->query(null, 'app', null, 'Test log', 10, 0);
        $this->assertCount(1, $items);
        $this->assertSame('Test log entry', $items[0]['message']);

        $stats = $reader->severityStats(24);
        $this->assertSame(1, $stats['info']);
        $this->assertSame(0, $stats['error']);
    }

    public function testCountAndPaginationRespectFilters(): void
    {
        $file = $this->baseDir . '/app/' . date('Y-m-d') . '.json';
        file_put_contents($file, JsonHelper::encode([
            [
                'id' => 'log_one',
                'timestamp' => date('Y-m-d H:i:s'),
                'severity' => LogSeverity::INFO,
                'category' => 'app',
                'message' => 'First entry',
            ],
            [
                'id' => 'log_two',
                'timestamp' => date('Y-m-d H:i:s', time() - 60),
                'severity' => LogSeverity::ERROR,
                'category' => 'app',
                'message' => 'Second entry',
            ],
        ]));

        $reader = new ApplicationLogReader([
            'app' => $this->baseDir . '/app',
        ]);

        $this->assertSame(2, $reader->count(null, 'app', null, null, 'active'));
        $this->assertCount(1, $reader->query(null, 'app', null, null, 1, 0, 'active'));
        $this->assertCount(1, $reader->query(null, 'app', null, null, 1, 1, 'active'));
    }

    public function testDeleteByIdsRemovesEntries(): void
    {
        $file = $this->baseDir . '/app/' . date('Y-m-d') . '.json';
        file_put_contents($file, JsonHelper::encode([
            [
                'id' => 'log_keep',
                'timestamp' => date('Y-m-d H:i:s'),
                'severity' => LogSeverity::INFO,
                'category' => 'app',
                'message' => 'Keep me',
            ],
            [
                'id' => 'log_delete',
                'timestamp' => date('Y-m-d H:i:s'),
                'severity' => LogSeverity::WARNING,
                'category' => 'app',
                'message' => 'Delete me',
            ],
        ]));

        $reader = new ApplicationLogReader([
            'app' => $this->baseDir . '/app',
        ]);

        $batch = $reader->deleteByIds(['log_delete', 'missing']);
        $this->assertSame(1, $batch->succeeded());
        $this->assertSame(1, $batch->failed());
        $this->assertSame(1, $reader->count(null, 'app', null, null, 'active'));

        $remaining = $reader->query(null, 'app', null, null, 10, 0, 'active');
        $this->assertSame('log_keep', $remaining[0]['id']);
    }

    public function testArchiveByIdsMarksEntriesArchived(): void
    {
        $file = $this->baseDir . '/app/' . date('Y-m-d') . '.json';
        file_put_contents($file, JsonHelper::encode([
            [
                'id' => 'log_archive',
                'timestamp' => date('Y-m-d H:i:s'),
                'severity' => LogSeverity::INFO,
                'category' => 'app',
                'message' => 'Archive me',
            ],
        ]));

        $reader = new ApplicationLogReader([
            'app' => $this->baseDir . '/app',
        ]);

        $batch = $reader->archiveByIds(['log_archive']);
        $this->assertSame(1, $batch->succeeded());
        $this->assertSame(0, $reader->count(null, 'app', null, null, 'active'));
        $this->assertSame(1, $reader->count(null, 'app', null, null, 'archived'));
    }

    public function testDeleteAllRemovesAllFiles(): void
    {
        file_put_contents($this->baseDir . '/app/' . date('Y-m-d') . '.json', JsonHelper::encode([
            ['id' => 'log_a', 'timestamp' => date('Y-m-d H:i:s'), 'severity' => LogSeverity::INFO, 'category' => 'app', 'message' => 'A'],
        ]));
        file_put_contents($this->baseDir . '/audit/' . date('Y-m-d') . '.json', JsonHelper::encode([
            ['id' => 'log_b', 'timestamp' => date('Y-m-d H:i:s'), 'severity' => LogSeverity::INFO, 'category' => 'audit', 'message' => 'B'],
        ]));

        $reader = new ApplicationLogReader([
            'app' => $this->baseDir . '/app',
            'audit' => $this->baseDir . '/audit',
        ]);

        $result = $reader->deleteAll();
        $this->assertSame(2, $result['deleted_files']);
        $this->assertSame(2, $result['deleted_entries']);
        $this->assertSame(0, $reader->count(null, null, null, null, 'all'));
    }

    public function testLogStoragePathsPointsToAppStorage(): void
    {
        $expectedSuffix = '/backend/app/storage/logs/app';
        $this->assertStringEndsWith($expectedSuffix, LogStoragePaths::app());
    }

    private function removeDirectory(string $dir): void
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
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
