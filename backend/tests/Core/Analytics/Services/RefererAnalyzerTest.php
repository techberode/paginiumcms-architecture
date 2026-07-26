<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Services\AnalyticsIpMasker;
use PaginiumCMS\Core\Analytics\Services\RefererAnalyzer;
use PHPUnit\Framework\TestCase;

final class RefererAnalyzerTest extends TestCase
{
    private RefererAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new RefererAnalyzer();
    }

    public function testDirectTraffic(): void
    {
        $result = $this->analyzer->analyze('');

        $this->assertSame('direct', $result['type']);
        $this->assertSame('Direct', $result['source']);
    }

    public function testGoogleSearchReferer(): void
    {
        $result = $this->analyzer->analyze('https://www.google.com/search?q=paginium');

        $this->assertSame('search', $result['type']);
        $this->assertSame('Google', $result['source']);
        $this->assertSame('www.google.com', $result['domain']);
    }

    public function testFacebookSocialReferer(): void
    {
        $result = $this->analyzer->analyze('https://l.facebook.com/l.php?u=https://example.com');

        $this->assertSame('social', $result['type']);
        $this->assertSame('Facebook', $result['source']);
    }
}

final class AnalyticsIpMaskerTest extends TestCase
{
    public function testMasksIpv4(): void
    {
        $this->assertSame('192.168.xxx.xxx', AnalyticsIpMasker::mask('192.168.1.42'));
    }

    public function testEmptyIp(): void
    {
        $this->assertSame('unknown', AnalyticsIpMasker::mask(''));
    }
}
