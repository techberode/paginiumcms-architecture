<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Demo;

use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PHPUnit\Framework\TestCase;

final class DemoModeTest extends TestCase
{
    private string $previousDemoMode = '';

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

    public function testResolveContentBasePathUsesDemoWhenEnabled(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';

        $path = DemoMode::resolveContentBasePath('/var/app/storage/app');

        $this->assertSame('/var/app/storage/app/demo', $path);
    }

    public function testResolveContentBasePathUsesProductionWhenDisabled(): void
    {
        putenv('DEMO_MODE=false');
        $_ENV['DEMO_MODE'] = 'false';

        $path = DemoMode::resolveContentBasePath('/var/app/storage/app');

        $this->assertSame('/var/app/storage/app/content', $path);
    }
}
