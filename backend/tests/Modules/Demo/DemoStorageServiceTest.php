<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Demo;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Demo\Services\DemoStorageService;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class DemoStorageServiceTest extends TestCase
{
    private string $previousDemoMode = '';

    protected function setUp(): void
    {
        $this->previousDemoMode = (string) (getenv('DEMO_MODE') ?: '');
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';
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

    public function testResetWritesOnlyToDemoPath(): void
    {
        vfsStream::setup('root', null, [
            'storage' => [
                'app' => [
                    'content' => [
                        'pages' => ['real-page.md' => '# real'],
                    ],
                    'demo' => [],
                ],
            ],
        ]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root/storage/app/demo'));

        $service = new DemoStorageService(new DemoMode(), $reader);
        $result = $service->reset();

        $this->assertSame(8, $result['written']);
        $this->assertFileExists(vfsStream::url('root/storage/app/demo/pages/home.md'));
        $this->assertFileExists(vfsStream::url('root/storage/app/content/pages/real-page.md'));
        $this->assertStringContainsString('real-page.md', vfsStream::url('root/storage/app/content/pages/real-page.md'));
    }

    public function testResetFailsWhenDemoModeDisabled(): void
    {
        putenv('DEMO_MODE=false');
        $_ENV['DEMO_MODE'] = 'false';

        vfsStream::setup('root');
        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root/content'));

        $service = new DemoStorageService(new DemoMode(), $reader);

        $this->expectException(\RuntimeException::class);
        $service->reset();
    }
}
