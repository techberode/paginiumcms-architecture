<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Extensions\Capabilities;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityAuditor;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityBroker;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityCatalog;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PHPUnit\Framework\TestCase;

final class PluginCapabilityAuditorTest extends TestCase
{
    public function testUsedWritesSanitizedAuditEvent(): void
    {
        $baseDir = sys_get_temp_dir() . '/pag_cap_audit_' . uniqid('', true);
        mkdir($baseDir . '/data/security', 0777, true);
        $reader = new FileReader(new FileValidator($baseDir));
        $audit = new SecurityAuditStore($reader, 'data/security/audit_events.json');
        $page = new Page();
        $page->setSlug('about');
        $page->setTitle('About');
        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findBySlug')->willReturn($page);
        $broker = new PluginCapabilityBroker(
            $content,
            $this->createStub(MediaRepositoryInterface::class),
            new PluginCapabilityAuditor($audit)
        );

        $broker->context('hello-widget', [PluginCapabilityCatalog::CONTENT_READ])->content()->get('page', 'about');

        $events = $audit->list(['type' => 'plugin_capability']);
        $this->assertCount(1, $events);
        $this->assertSame('plugin hello-widget used content:read', $events[0]['message'] ?? '');

        $this->removeDir($baseDir);
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
