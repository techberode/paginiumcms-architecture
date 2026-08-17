<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Support\DevStorageHygiene;
use PHPUnit\Framework\TestCase;

final class DevStorageHygieneTest extends TestCase
{
    public function testScanIncludesPrefixOnlyCategories(): void
    {
        $counts = DevStorageHygiene::scan();

        $this->assertArrayHasKey('test_pages', $counts);
        $this->assertArrayHasKey('test_drafts', $counts);
        $this->assertArrayHasKey('test_versions', $counts);
        $this->assertArrayNotHasKey('pages', $counts);
    }

    public function testProductionEnvironmentIsBlockedUnlessForced(): void
    {
        $previous = getenv('APP_ENV');
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';

        try {
            $this->expectException(\RuntimeException::class);
            DevStorageHygiene::assertAllowedEnvironment(false);
        } finally {
            if ($previous === false) {
                putenv('APP_ENV');
                unset($_ENV['APP_ENV']);
            } else {
                putenv('APP_ENV=' . $previous);
                $_ENV['APP_ENV'] = $previous;
            }
        }
    }

    public function testFormatReportMentionsPrefixSafety(): void
    {
        $formatted = DevStorageHygiene::formatReport([
            'before' => ['test_pages' => 2],
            'after' => ['test_pages' => 0],
            'rebuilt_index' => false,
        ]);

        $this->assertStringContainsString('prefix', strtolower($formatted));
        $this->assertStringContainsString('Preserved', $formatted);
    }
}
