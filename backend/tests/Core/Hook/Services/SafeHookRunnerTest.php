<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Hook\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Hook\HookManager;
use PaginiumCMS\Core\Hook\PluginHookListener;
use PaginiumCMS\Core\Hook\Services\SafeHookRunner;
use PaginiumCMS\Http\Extensions\Models\PluginRecord;
use PaginiumCMS\Http\Extensions\Services\PluginHealthStore;
use PaginiumCMS\Http\Extensions\Services\PluginRegistry;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

final class SafeHookRunnerTest extends TestCase
{
    private string $baseDir;

    private PluginRegistry $registry;

    private PluginHealthStore $health;

    private SecurityAuditStore $audit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_safe_hook_' . uniqid('', true);
        mkdir($this->baseDir . '/data/plugins', 0777, true);
        mkdir($this->baseDir . '/data/security', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $this->registry = new PluginRegistry($reader, $writer, 'data/plugins.json');
        $this->health = new PluginHealthStore($reader, $writer, 'data/plugins/health.json');
        $this->audit = new SecurityAuditStore($reader, 'data/security/audit_events.json');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testFailingPluginDoesNotStopOtherListeners(): void
    {
        $this->registry->upsert(new PluginRecord('boom', true, 't'));
        $this->registry->upsert(new PluginRecord('ok', true, 't'));
        $hooks = $this->hooks();

        $hooks->add('demo', new PluginHookListener('boom', static function (): string {
            throw new RuntimeException('plugin exploded');
        }));
        $hooks->add('demo', new PluginHookListener('ok', static function (): string {
            return 'alive';
        }));

        $results = $hooks->run('demo', [[]]);

        $this->assertNull($results[0]);
        $this->assertSame('alive', $results[1]);
        $this->assertPluginEnabled('ok', true);
    }

    public function testThreeExceptionsAutoDisableThePlugin(): void
    {
        $this->registry->upsert(new PluginRecord('flaky', true, 't'));
        $hooks = $this->hooks();
        $hooks->add('demo', new PluginHookListener('flaky', static function (): string {
            throw new RuntimeException('nope');
        }));

        $hooks->run('demo', [[]]);
        $hooks->run('demo', [[]]);
        $this->assertPluginEnabled('flaky', true);

        $hooks->run('demo', [[]]);
        $this->assertPluginEnabled('flaky', false);
        $health = $this->health->snapshot('flaky');
        $this->assertNotNull($health);
        $this->assertTrue($health['autoDisabled']);

        $types = array_map(
            static fn (array $row): string => (string) ($row['type'] ?? ''),
            $this->audit->list()
        );
        $this->assertContains('plugin_hook_failed', $types);
        $this->assertContains('plugin_auto_disabled', $types);
    }

    public function testFatalErrorDisablesImmediately(): void
    {
        $this->registry->upsert(new PluginRecord('broken', true, 't'));
        $hooks = $this->hooks();
        $hooks->add('demo', new PluginHookListener('broken', static function (): string {
            throw new TypeError('bad signature');
        }));

        $hooks->run('demo', [['id' => 'x']]);

        $this->assertPluginEnabled('broken', false);
        $health = $this->health->snapshot('broken');
        $this->assertNotNull($health);
        $this->assertTrue($health['autoDisabled']);
    }

    public function testQuotaExceededCountsTowardAutoDisable(): void
    {
        $this->registry->upsert(new PluginRecord('slow', true, 't'));
        $runner = new SafeHookRunner($this->registry, $this->health, $this->audit, 0, 16_777_216, 3);
        $hooks = new HookManager($runner);
        $hooks->add('demo', new PluginHookListener('slow', static function (): string {
            return 'done';
        }));

        $this->assertSame(['done'], $hooks->run('demo', [[]]));
        $this->assertSame(['done'], $hooks->run('demo', [[]]));
        $this->assertPluginEnabled('slow', true);

        $this->assertSame(['done'], $hooks->run('demo', [[]]));
        $this->assertPluginEnabled('slow', false);
    }

    public function testDisabledPluginIsSkippedOnLaterInvocations(): void
    {
        $this->registry->upsert(new PluginRecord('broken', true, 't'));
        $hooks = $this->hooks();
        $calls = 0;
        $hooks->add('demo', new PluginHookListener('broken', static function () use (&$calls): string {
            $calls++;
            throw new TypeError('once');
        }));

        $hooks->run('demo', [[]]);
        $hooks->run('demo', [[]]);

        $this->assertSame(1, $calls);
    }

    private function assertPluginEnabled(string $id, bool $enabled): void
    {
        $record = $this->registry->get($id);
        $this->assertNotNull($record);
        $this->assertSame($enabled, $record->enabled);
    }

    private function hooks(): HookManager
    {
        return new HookManager(new SafeHookRunner($this->registry, $this->health, $this->audit));
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
