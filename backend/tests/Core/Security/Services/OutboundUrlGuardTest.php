<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Services;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Overuje SSRF ochranu odchádzajúcich URL (audit C14).
 *
 * Testy používajú IP literály (žiadny DNS lookup), aby boli deterministické.
 */
final class OutboundUrlGuardTest extends TestCase
{
    private function strict(): OutboundUrlGuard
    {
        return new OutboundUrlGuard(allowHttp: false, allowPrivate: false);
    }

    public function testAllowsHttpsPublicIp(): void
    {
        $this->assertTrue($this->strict()->isAllowed('https://8.8.8.8/token'));
    }

    public function testBlocksHttpInStrictMode(): void
    {
        $this->assertFalse($this->strict()->isAllowed('http://8.8.8.8/token'));
    }

    public function testBlocksLoopback(): void
    {
        $this->assertFalse($this->strict()->isAllowed('https://127.0.0.1/'));
    }

    public function testBlocksCloudMetadataLinkLocal(): void
    {
        // AWS/GCP metadata endpoint – klasický SSRF cieľ.
        $this->assertFalse($this->strict()->isAllowed('https://169.254.169.254/latest/meta-data/'));
    }

    public function testBlocksPrivateRanges(): void
    {
        $this->assertFalse($this->strict()->isAllowed('https://10.0.0.5/'));
        $this->assertFalse($this->strict()->isAllowed('https://192.168.1.1/'));
        $this->assertFalse($this->strict()->isAllowed('https://172.16.0.9/'));
    }

    public function testBlocksIpv6Loopback(): void
    {
        $this->assertFalse($this->strict()->isAllowed('https://[::1]/'));
    }

    public function testBlocksUserinfo(): void
    {
        // https://user:pass@host trik na obídenie naivnej host kontroly.
        $this->assertFalse($this->strict()->isAllowed('https://evil:pass@8.8.8.8/'));
    }

    public function testBlocksNonHttpSchemes(): void
    {
        $this->assertFalse($this->strict()->isAllowed('file:///etc/passwd'));
        $this->assertFalse($this->strict()->isAllowed('gopher://8.8.8.8/'));
    }

    public function testBlocksMalformedUrl(): void
    {
        $this->assertFalse($this->strict()->isAllowed('not a url'));
        $this->assertFalse($this->strict()->isAllowed(''));
    }

    public function testAssertThrowsOnBlocked(): void
    {
        $this->expectException(RuntimeException::class);
        $this->strict()->assertAllowed('https://127.0.0.1/');
    }

    public function testRelaxedModeAllowsLocalhostHttp(): void
    {
        // Dev/test: lokálny SSO/ntfy na http://localhost musí prejsť.
        $relaxed = new OutboundUrlGuard(allowHttp: true, allowPrivate: true);
        $this->assertTrue($relaxed->isAllowed('http://127.0.0.1:8080/token'));
    }
}
