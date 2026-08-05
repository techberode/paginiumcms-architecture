<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Demo;

use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Demo\Services\DemoStorageQuotaService;
use PHPUnit\Framework\TestCase;

final class DemoStorageQuotaServiceTest extends TestCase
{
    private string $baseDir;
    private string $previousDemoMode = '';
    private string|false|null $previousQuotaBytes = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousDemoMode = (string) (getenv('DEMO_MODE') ?: '');
        $this->previousQuotaBytes = getenv('DEMO_STORAGE_QUOTA_BYTES');
        $this->baseDir = sys_get_temp_dir() . '/pag_demo_quota_' . uniqid('', true);
        mkdir($this->baseDir . '/app/demo/data', 0777, true);
        file_put_contents($this->baseDir . '/app/demo/data/settings.json', str_repeat('x', 1024));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);

        if ($this->previousDemoMode !== '') {
            putenv('DEMO_MODE=' . $this->previousDemoMode);
            $_ENV['DEMO_MODE'] = $this->previousDemoMode;
            $_SERVER['DEMO_MODE'] = $this->previousDemoMode;
        } else {
            putenv('DEMO_MODE=false');
            $_ENV['DEMO_MODE'] = 'false';
            $_SERVER['DEMO_MODE'] = 'false';
        }

        if ($this->previousQuotaBytes === false) {
            putenv('DEMO_STORAGE_QUOTA_BYTES');
            unset($_ENV['DEMO_STORAGE_QUOTA_BYTES']);
        } elseif ($this->previousQuotaBytes !== null) {
            putenv('DEMO_STORAGE_QUOTA_BYTES=' . $this->previousQuotaBytes);
            $_ENV['DEMO_STORAGE_QUOTA_BYTES'] = $this->previousQuotaBytes;
        } else {
            putenv('DEMO_STORAGE_QUOTA_BYTES');
            unset($_ENV['DEMO_STORAGE_QUOTA_BYTES']);
        }

        parent::tearDown();
    }

    public function testMetricsUsesSandboxQuotaNotHostDisk(): void
    {
        putenv('DEMO_MODE=true');
        putenv('DEMO_STORAGE_QUOTA_BYTES=300000000');
        $_ENV['DEMO_MODE'] = 'true';
        $_ENV['DEMO_STORAGE_QUOTA_BYTES'] = '300000000';
        $_SERVER['DEMO_MODE'] = 'true';

        $service = new DemoStorageQuotaService(new DemoMode());
        $metrics = $service->metrics($this->baseDir);

        $this->assertSame(300_000_000, $metrics['quota_bytes']);
        $this->assertGreaterThanOrEqual(1024, $metrics['used_space_bytes']);
        $this->assertSame(300_000_000 - $metrics['used_space_bytes'], $metrics['free_space_bytes']);
    }

    public function testDirectorySizeIgnoresMissingPath(): void
    {
        $this->assertSame(0, DemoStorageQuotaService::directorySizeBytes($this->baseDir . '/missing'));
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
}
