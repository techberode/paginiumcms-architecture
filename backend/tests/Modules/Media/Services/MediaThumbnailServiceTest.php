<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Modules\Media\Services\MediaThumbnailService;
use PHPUnit\Framework\TestCase;

final class MediaThumbnailServiceTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/paginium-thumb-' . uniqid('', true);
        mkdir($this->fixtureDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->fixtureDir);
    }

    public function testEnsureCreatesSmallerCachedThumbnail(): void
    {
        if (!(new MediaThumbnailService())->isAvailable()) {
            $this->markTestSkipped('GD extension is not available.');
        }

        $source = $this->fixtureDir . '/source.png';
        $image = imagecreatetruecolor(800, 400);
        for ($y = 0; $y < 400; ++$y) {
            for ($x = 0; $x < 800; ++$x) {
                $color = imagecolorallocate($image, ($x + $y) % 256, ($x * 3) % 256, ($y * 5) % 256);
                if ($color !== false) {
                    imagesetpixel($image, $x, $y, $color);
                }
            }
        }
        imagepng($image, $source, 0);
        imagedestroy($image);

        $service = new MediaThumbnailService();
        $thumbPath = $service->ensure($source, 480);

        $this->assertNotNull($thumbPath);
        $this->assertFileExists($thumbPath);
        $this->assertLessThan(
            filesize($source),
            filesize($thumbPath),
            'Thumbnail should be smaller than the source image.',
        );

        $info = getimagesize($thumbPath);
        $this->assertIsArray($info);
        $this->assertSame(480, $info[0]);
    }

    public function testEnsureReturnsNullWhenSourceAlreadySmall(): void
    {
        if (!(new MediaThumbnailService())->isAvailable()) {
            $this->markTestSkipped('GD extension is not available.');
        }

        $source = $this->fixtureDir . '/small.jpg';
        $image = imagecreatetruecolor(320, 200);
        imagejpeg($image, $source, 90);
        imagedestroy($image);

        $service = new MediaThumbnailService();
        $this->assertNull($service->ensure($source, 480));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
