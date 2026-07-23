<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Hook\Services;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Core\Hook\HookManager;
use PaginiumCMS\Core\Hook\Services\HookEmitter;
use PHPUnit\Framework\TestCase;

final class HookEmitterTest extends TestCase
{
    public function testEmitRunsRegisteredHookWithContext(): void
    {
        $manager = new HookManager();
        $received = null;
        $manager->add(HookCatalog::CONTENT_AFTER_SAVE, static function (array $context) use (&$received): void {
            $received = $context;
        });

        $emitter = new HookEmitter($manager);
        $emitter->emit(HookCatalog::CONTENT_AFTER_SAVE, [
            'type' => 'page',
            'slug' => 'demo',
            'status' => 'draft',
            'action' => 'create',
            'userId' => 'u1',
        ]);

        $this->assertSame('demo', $received['slug'] ?? null);
    }

    public function testEmitIgnoresUnknownHookNames(): void
    {
        $manager = new HookManager();
        $called = false;
        $manager->add('legacy.custom', static function () use (&$called): void {
            $called = true;
        });

        $emitter = new HookEmitter($manager);
        $emitter->emit('legacy.custom');

        $this->assertFalse($called);
    }

    public function testCatalogReturnsDescriptions(): void
    {
        $emitter = new HookEmitter(new HookManager());
        $catalog = $emitter->catalog();

        $this->assertArrayHasKey(HookCatalog::EXTENSION_BOOT, $catalog);
        $this->assertNotSame('', $catalog[HookCatalog::EXTENSION_BOOT]['description']);
    }
}
