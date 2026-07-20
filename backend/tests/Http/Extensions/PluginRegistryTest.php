<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Extensions;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Http\Extensions\Models\PluginRecord;
use PaginiumCMS\Http\Extensions\Services\PluginRegistry;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;

final class PluginRegistryTest extends TestCase
{
    private string $baseDir;
    private PluginRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_plugins_registry_' . uniqid('', true);
        mkdir($this->baseDir, 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $this->registry = new PluginRegistry($reader, $writer, 'data/plugins.json');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testUpsertAndGet(): void
    {
        $record = new PluginRecord('demo-plugin', true, '2026-07-20T10:00:00+00:00');
        $this->registry->upsert($record);

        $loaded = $this->registry->get('demo-plugin');
        $this->assertNotNull($loaded);
        $this->assertTrue($loaded->enabled);
        $this->assertSame('2026-07-20T10:00:00+00:00', $loaded->installedAt);
    }

    public function testRemove(): void
    {
        $this->registry->upsert(new PluginRecord('demo-plugin', false, '2026-07-20T10:00:00+00:00'));
        $this->registry->remove('demo-plugin');

        $this->assertNull($this->registry->get('demo-plugin'));
    }

    public function testPersistsJsonOnDisk(): void
    {
        $this->registry->upsert(new PluginRecord('alpha', true, '2026-07-20T10:00:00+00:00'));

        $path = $this->baseDir . '/data/plugins.json';
        $this->assertFileExists($path);

        $decoded = JsonHelper::decode((string) file_get_contents($path));
        $this->assertArrayHasKey('alpha', $decoded);
        $this->assertTrue($decoded['alpha']['enabled']);
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
