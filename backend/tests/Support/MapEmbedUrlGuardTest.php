<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Support\MapEmbedUrlGuard;
use PHPUnit\Framework\TestCase;

final class MapEmbedUrlGuardTest extends TestCase
{
    public function testAllowsGoogleEmbed(): void
    {
        $url = 'https://www.google.com/maps/embed?pb=abc';
        $this->assertTrue(MapEmbedUrlGuard::isAllowed($url));
        $this->assertStringContainsString('maps/embed', MapEmbedUrlGuard::sanitizeSrc($url));
    }

    public function testRejectsNonGoogleHost(): void
    {
        $this->assertFalse(MapEmbedUrlGuard::isAllowed('https://maps.google.com/embed'));
        $this->assertSame('', MapEmbedUrlGuard::sanitizeSrc('https://example.com/maps/embed'));
    }
}
