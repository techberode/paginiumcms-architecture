<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Models\EditorComponentDefinition;
use PaginiumCMS\Core\Editor\Services\EditorComponentRegistry;
use PaginiumCMS\Http\Extensions\Contracts\PluginManagerInterface;
use PHPUnit\Framework\TestCase;

final class EditorComponentRegistryTest extends TestCase
{
    public function testListsComponentsFromEnabledPlugins(): void
    {
        $plugins = $this->createMock(PluginManagerInterface::class);
        $plugins->method('listEnabledEditorComponents')->willReturn([
            new EditorComponentDefinition(
                'hello-widget',
                'Hello Widget',
                'hello-widget',
                'hello-widget',
                'helloWidget'
            ),
        ]);

        $registry = new EditorComponentRegistry($plugins);
        $items = $registry->listRegisteredForApi();

        $this->assertCount(1, $items);
        $this->assertSame('hello-widget', $items[0]['id']);
        $this->assertSame('helloWidget', $items[0]['tiptapNodeType']);
    }
}
