<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Cache\Contracts\AdminOverviewCacheWarmRunner;
use PaginiumCMS\Core\Scheduler\Handlers\AdminOverviewCacheWarmHandler;
use PHPUnit\Framework\TestCase;

final class AdminOverviewCacheWarmHandlerTest extends TestCase
{
    public function testWarmAllSegments(): void
    {
        $warmer = $this->createMock(AdminOverviewCacheWarmRunner::class);
        $warmer->expects($this->once())->method('warmAll')->willReturn(['audit_stats', 'metrics_apm']);

        $handler = new AdminOverviewCacheWarmHandler($warmer);
        $result = $handler->handle();

        $this->assertTrue($result->success);
        $this->assertSame('admin_cache.warm', $handler->key());
        $this->assertStringContainsString('audit_stats', $result->message);
    }
}
