<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Performance;

use PaginiumCMS\Core\Performance\HostMetricsSnapshotSanitizer;
use PHPUnit\Framework\TestCase;

final class HostMetricsSnapshotSanitizerTest extends TestCase
{
    public function testSanitizeAcceptsMinimalPayload(): void
    {
        $snapshot = HostMetricsSnapshotSanitizer::sanitize([
            'collected_at' => '2026-10-10T08:00:00+00:00',
            'load' => ['1' => 0.5, '5' => 0.4, '15' => 0.3],
            'memory' => ['total_mb' => 32000, 'used_mb' => 18000, 'available_mb' => 14000],
            'disk' => [
                'mount' => '/',
                'total_gb' => 500,
                'used_gb' => 200,
                'available_gb' => 300,
                'used_percent' => 40,
            ],
        ]);

        $this->assertIsArray($snapshot);
        $this->assertSame(32000, $snapshot['memory']['total_mb']);
        $this->assertSame(40.0, $snapshot['disk']['used_percent']);
    }

    public function testRejectMissingCollectedAt(): void
    {
        $this->assertNull(HostMetricsSnapshotSanitizer::sanitize(['load' => ['1' => 1.0]]));
    }
}
