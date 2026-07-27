<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler\Services;

use PaginiumCMS\Core\Scheduler\Services\PrivilegedJobPolicy;
use PHPUnit\Framework\TestCase;

final class PrivilegedJobPolicyTest extends TestCase
{
    public function testSystemDeployRequiresSuperAdmin(): void
    {
        $this->assertTrue(PrivilegedJobPolicy::requiresSuperAdmin([
            'id' => 'system-deploy',
            'handler' => 'system.deploy',
        ]));
    }

    public function testBackupJobDoesNotRequireSuperAdmin(): void
    {
        $this->assertFalse(PrivilegedJobPolicy::requiresSuperAdmin([
            'id' => 'backup-scheduled',
            'handler' => 'backup.scheduled',
        ]));
    }

    public function testSystemDeploySkippedInRunDue(): void
    {
        $this->assertTrue(PrivilegedJobPolicy::skipInScheduledRunDue('system.deploy'));
        $this->assertFalse(PrivilegedJobPolicy::skipInScheduledRunDue('backup.scheduled'));
    }
}
