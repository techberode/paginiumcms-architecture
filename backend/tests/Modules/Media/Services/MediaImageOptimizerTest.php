<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Media\Services\MediaImageOptimizer;
use PHPUnit\Framework\TestCase;

final class MediaImageOptimizerTest extends TestCase
{
    public function testOptimizesLargePng(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $image = imagecreatetruecolor(800, 600);
        $this->assertNotFalse($image);
        $blue = imagecolorallocate($image, 30, 90, 200);
        $this->assertNotFalse($blue);
        imagefilledrectangle($image, 0, 0, 799, 599, $blue);

        ob_start();
        imagepng($image, null, 0);
        imagedestroy($image);
        $binary = ob_get_clean();
        $this->assertGreaterThan(10_000, strlen($binary));

        $optimizer = new MediaImageOptimizer();
        $result = $optimizer->optimize($binary, 'image/png');

        $this->assertLessThan($result['beforeBytes'], $result['afterBytes']);
        $this->assertSame(800, $result['width']);
        $this->assertSame(600, $result['height']);
        $this->assertGreaterThan(64, $result['savedBytes']);
    }

    public function testRejectsSvg(): void
    {
        $optimizer = new MediaImageOptimizer();

        $this->expectException(FlatFileException::class);
        $optimizer->optimize('<svg xmlns="http://www.w3.org/2000/svg"></svg>', 'image/svg+xml');
    }

    public function testOptimizesWithResize(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $image = imagecreatetruecolor(800, 600);
        $this->assertNotFalse($image);
        $blue = imagecolorallocate($image, 30, 90, 200);
        $this->assertNotFalse($blue);
        imagefilledrectangle($image, 0, 0, 799, 599, $blue);

        ob_start();
        imagepng($image, null, 0);
        imagedestroy($image);
        $binary = ob_get_clean();
        $this->assertGreaterThan(10_000, strlen($binary));

        $optimizer = new MediaImageOptimizer();
        $result = $optimizer->optimize($binary, 'image/png', 400, null);

        $this->assertSame(400, $result['width']);
        $this->assertSame(300, $result['height']);
        $this->assertLessThan($result['beforeBytes'], $result['afterBytes']);
    }

    public function testInspectReturnsDimensions(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $image = imagecreatetruecolor(640, 480);
        $this->assertNotFalse($image);
        ob_start();
        imagepng($image);
        imagedestroy($image);
        $binary = ob_get_clean();

        $optimizer = new MediaImageOptimizer();
        $info = $optimizer->inspect($binary);

        $this->assertSame(640, $info['width']);
        $this->assertSame(480, $info['height']);
        $this->assertSame('image/png', $info['mimeType']);
    }

    public function testCapabilitiesWhenGdMissing(): void
    {
        $capabilities = MediaImageOptimizer::capabilities();

        if (extension_loaded('gd')) {
            $this->assertTrue($capabilities['available']);
            return;
        }

        $this->assertFalse($capabilities['available']);
    }

    public function testRejectsWhenAlreadySmall(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
        $this->assertIsString($png);

        $optimizer = new MediaImageOptimizer();

        $this->expectException(FlatFileException::class);
        $optimizer->optimize($png, 'image/png');
    }
}
