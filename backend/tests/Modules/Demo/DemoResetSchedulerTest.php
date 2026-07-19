<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Demo;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Demo\Services\DemoResetScheduler;
use PaginiumCMS\Modules\Demo\Services\DemoStorageService;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class DemoResetSchedulerTest extends TestCase
{
    private string $previousDemoMode = '';

    protected function setUp(): void
    {
        $this->previousDemoMode = (string) (getenv('DEMO_MODE') ?: '');
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';
        putenv('DEMO_AUTO_RESET_MINUTES=60');
        $_ENV['DEMO_AUTO_RESET_MINUTES'] = '60';
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
        putenv('DEMO_AUTO_RESET_MINUTES');
        unset($_ENV['DEMO_AUTO_RESET_MINUTES']);
    }

    public function testDoesNotRunWhenNotDue(): void
    {
        vfsStream::setup('root', null, [
            'storage' => [
                'app' => [
                    'demo' => [
                        '.meta' => [
                            'last-reset.json' => json_encode(['reset_at' => time()], JSON_THROW_ON_ERROR),
                        ],
                    ],
                    'content' => [],
                ],
            ],
        ]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root/storage/app/demo'));

        $storage = new DemoStorageService(new DemoMode(), $reader);
        $scheduler = new DemoResetScheduler(new DemoMode(), $storage);

        $result = $scheduler->runIfDue();

        $this->assertFalse($result['ran']);
        $this->assertSame('not_due', $result['reason'] ?? null);
    }

    public function testRunsWhenIntervalElapsed(): void
    {
        vfsStream::setup('root', null, [
            'storage' => [
                'app' => [
                    'demo' => [
                        '.meta' => [
                            'last-reset.json' => json_encode(['reset_at' => time() - 7200], JSON_THROW_ON_ERROR),
                        ],
                    ],
                    'content' => [],
                ],
            ],
        ]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root/storage/app/demo'));

        $storage = new DemoStorageService(new DemoMode(), $reader);
        $scheduler = new DemoResetScheduler(new DemoMode(), $storage);

        $result = $scheduler->runIfDue();

        $this->assertTrue($result['ran']);
        $this->assertGreaterThan(0, $result['written'] ?? 0);
        $this->assertFileExists(vfsStream::url('root/storage/app/demo/data/users/demo_admin_user.json'));
    }
}
