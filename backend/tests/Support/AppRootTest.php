<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Support\AppRoot;
use PHPUnit\Framework\TestCase;

final class AppRootTest extends TestCase
{
    public function testResolveFindsRepoRootFromFilesystem(): void
    {
        $root = AppRoot::resolve();
        $this->assertNotNull($root);
        $this->assertTrue(AppRoot::isRepoRoot($root));
        $this->assertFileExists($root . '/scripts/deploy-instance-update.sh');
    }

    public function testResolvePrefersPathThatContainsDeployScript(): void
    {
        $root = AppRoot::resolve('/tmp/not-a-repo');
        $this->assertNotNull($root);
        $this->assertFileExists($root . '/scripts/deploy-instance-update.sh');
    }
}
