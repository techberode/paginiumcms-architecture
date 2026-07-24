<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Services;

use PaginiumCMS\Core\Security\Services\ZipEntryGuard;
use PHPUnit\Framework\TestCase;

class ZipEntryGuardTest extends TestCase
{
    private ZipEntryGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new ZipEntryGuard();
    }

    public function testAcceptsNormalRelativePath(): void
    {
        $this->assertTrue($this->guard->isSafeEntry('content/pages/home.json'));
    }

    public function testRejectsParentTraversal(): void
    {
        $this->assertFalse($this->guard->isSafeEntry('../etc/passwd'));
        $this->assertFalse($this->guard->isSafeEntry('content/../../secret.txt'));
    }

    public function testRejectsAbsolutePaths(): void
    {
        $this->assertFalse($this->guard->isSafeEntry('/etc/passwd'));
        $this->assertFalse($this->guard->isSafeEntry('C:/Windows/system.ini'));
    }
}
