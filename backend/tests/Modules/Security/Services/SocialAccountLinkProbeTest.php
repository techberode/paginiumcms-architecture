<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Modules\Security\Services\SocialAccountLinkProbe;
use PHPUnit\Framework\TestCase;

final class SocialAccountLinkProbeTest extends TestCase
{
    private SocialAccountLinkProbe $probe;

    protected function setUp(): void
    {
        $this->probe = new SocialAccountLinkProbe(new OutboundUrlGuard(true, true));
    }

    public function testNormalizesTelegramHandle(): void
    {
        $this->assertSame(
            'https://t.me/desk',
            SocialAccountLinkProbe::normalizeStorageUrl('telegram', '@desk')
        );
    }

    public function testVerifiesEmailWithoutHttp(): void
    {
        $result = $this->probe->verify('email', 'ada@example.com');

        $this->assertTrue($result['ok']);
        $this->assertSame('ada@example.com', $result['normalizedUrl']);
    }

    public function testRejectsMismatchedPlatformHost(): void
    {
        $result = $this->probe->verify('github', 'https://example.com/user');

        $this->assertFalse($result['ok']);
    }
}
