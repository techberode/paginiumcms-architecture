<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Extensions;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Http\Extensions\HelloWidget\Hooks;
use PaginiumCMS\Tests\Http\TestCase;

final class HelloWidgetReferencePluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $hooksFile = dirname(__DIR__, 3) . '/app/Http/Extensions/hello-widget/src/Hooks.php';
        if (is_file($hooksFile)) {
            require_once $hooksFile;
        }
        Hooks::reset();
    }

    public function testReferenceManifestUsesCatalogHooks(): void
    {
        $manifestPath = dirname(__DIR__, 3) . '/app/Http/Extensions/hello-widget/plugin.json';
        $this->assertFileExists($manifestPath);

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('hello-widget', $manifest['id']);
        $this->assertArrayHasKey(HookCatalog::EXTENSION_BOOT, $manifest['hooks']);
        $this->assertArrayHasKey(HookCatalog::CONTENT_AFTER_SAVE, $manifest['hooks']);
    }

    public function testReferenceHookHandlersAreCallable(): void
    {
        Hooks::onBoot(['id' => 'hello-widget', 'manifest' => []]);
        $this->assertTrue(Hooks::$booted);

        Hooks::onContentAfterSave(['slug' => 'demo', 'type' => 'page']);
        $this->assertSame('demo', Hooks::$lastContentContext['slug'] ?? null);
    }
}
