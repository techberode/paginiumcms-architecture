<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Demo;

use PaginiumCMS\Modules\Demo\Services\DemoDataProvider;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PHPUnit\Framework\TestCase;

final class DemoDataProviderTest extends TestCase
{
    private string $previousDemoMode;

    protected function setUp(): void
    {
        $this->previousDemoMode = (string) (getenv('DEMO_MODE') ?: '');
    }

    protected function tearDown(): void
    {
        if ($this->previousDemoMode !== '') {
            putenv('DEMO_MODE=' . $this->previousDemoMode);
            $_ENV['DEMO_MODE'] = $this->previousDemoMode;
        } else {
            putenv('DEMO_MODE');
            unset($_ENV['DEMO_MODE']);
        }
    }

    public function testReturnsEmptyWhenDemoModeDisabled(): void
    {
        putenv('DEMO_MODE=false');
        $_ENV['DEMO_MODE'] = 'false';

        $provider = new DemoDataProvider(new DemoMode());

        $this->assertFalse($provider->isEnabled());
        $this->assertSame([], $provider->comments());
        $this->assertSame([], $provider->contactMessages());
        $this->assertSame([], $provider->newsletterSubscribers());
    }

    public function testReturnsFixturesWhenDemoModeEnabled(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';

        $provider = new DemoDataProvider(new DemoMode());

        $this->assertTrue($provider->isEnabled());
        $this->assertNotEmpty($provider->comments());
        $this->assertNotEmpty($provider->contactMessages());
        $this->assertNotEmpty($provider->newsletterSubscribers());
    }

    public function testFiltersCommentsByArticleSlug(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';

        $provider = new DemoDataProvider(new DemoMode());
        $filtered = $provider->comments('uvod-do-flatfile');

        $this->assertCount(1, $filtered);
        $this->assertSame('Peter K.', $filtered[0]['author']);
    }
}
