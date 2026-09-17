<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Support\GitCli;
use PHPUnit\Framework\TestCase;

final class GitCliTest extends TestCase
{
    public function testAtIncludesSafeDirectoryAndChangeDirectory(): void
    {
        $root = dirname(__DIR__, 3);
        $prefix = GitCli::at($root);

        $this->assertStringContainsString('safe.directory=', $prefix);
        $this->assertStringContainsString('-C', $prefix);
        $this->assertStringContainsString('git', $prefix);
    }
}
