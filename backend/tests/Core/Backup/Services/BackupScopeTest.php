<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Backup\Services;

use PaginiumCMS\Core\Backup\Services\BackupScope;
use PHPUnit\Framework\TestCase;

final class BackupScopeTest extends TestCase
{
    public function testNormalizeIncludesDropsUnknownAndSubtreesWhenContentSelected(): void
    {
        $includes = BackupScope::normalizeIncludes(['content', 'pages', 'evil', 'config']);

        $this->assertSame(['content', 'config'], $includes);
    }

    public function testNormalizeIncludesKeepsGranularFlagsWithoutContent(): void
    {
        $includes = BackupScope::normalizeIncludes(['pages', 'blog', 'config']);

        $this->assertSame(['pages', 'blog', 'config'], $includes);
    }

    public function testEmptyRawFallsBackToDefault(): void
    {
        $this->assertSame([], BackupScope::sanitizeIncludes([]));
        $this->assertSame(BackupScope::DEFAULT_INCLUDES, BackupScope::normalizeIncludes([]));
    }

    public function testNormalizeMode(): void
    {
        $this->assertSame(BackupScope::MODE_FULL, BackupScope::normalizeMode(null));
        $this->assertSame(BackupScope::MODE_INCREMENTAL, BackupScope::normalizeMode('incremental'));
        $this->assertSame(BackupScope::MODE_FULL, BackupScope::normalizeMode('nope'));
    }
}
