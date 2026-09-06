<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\AvatarImageProcessor;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PHPUnit\Framework\TestCase;

final class AvatarImageProcessorTest extends TestCase
{
    public function testDownscalesLargeImage(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $image = imagecreatetruecolor(1200, 900);
        $this->assertNotFalse($image);
        $color = imagecolorallocate($image, 10, 120, 200);
        $this->assertNotFalse($color);
        imagefilledrectangle($image, 0, 0, 1199, 899, $color);
        ob_start();
        imagejpeg($image, null, 90);
        imagedestroy($image);
        $binary = ob_get_clean();
        $this->assertGreaterThan(1000, strlen($binary));

        $processor = new AvatarImageProcessor();
        $result = $processor->process($binary, 'image/jpeg');

        $info = getimagesizefromstring($result['binary']);
        $this->assertNotFalse($info);
        $this->assertLessThanOrEqual(AvatarImageProcessor::MAX_DIMENSION, $info[0]);
        $this->assertLessThanOrEqual(AvatarImageProcessor::MAX_DIMENSION, $info[1]);
        $this->assertLessThanOrEqual(AvatarImageProcessor::MAX_BYTES, strlen($result['binary']));
    }

    public function testRejectsNonImage(): void
    {
        $processor = new AvatarImageProcessor();

        $this->expectException(FlatFileException::class);
        $processor->process('not-an-image', 'image/jpeg');
    }
}
