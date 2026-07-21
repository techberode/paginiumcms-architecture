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
