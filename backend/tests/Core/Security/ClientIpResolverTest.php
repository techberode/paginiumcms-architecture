<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security;

use PaginiumCMS\Core\Security\ClientIpResolver;
use PHPUnit\Framework\TestCase;

final class ClientIpResolverTest extends TestCase
{
    public function testUsesRemoteAddrWithoutTrustedProxy(): void
    {
        $ip = ClientIpResolver::resolve(['REMOTE_ADDR' => '203.0.113.10'], []);

        $this->assertSame('203.0.113.10', $ip);
    }

    public function testUsesForwardedForFromTrustedProxy(): void
    {
        $ip = ClientIpResolver::resolve(
            [
                'REMOTE_ADDR' => '192.168.10.26',
                'HTTP_X_FORWARDED_FOR' => '192.168.10.100, 192.168.10.26',
            ],
            ['192.168.10.26']
        );

        $this->assertSame('192.168.10.100', $ip);
    }

    public function testIgnoresForwardedForFromUntrustedProxy(): void
    {
        $ip = ClientIpResolver::resolve(
            [
                'REMOTE_ADDR' => '203.0.113.1',
                'HTTP_X_FORWARDED_FOR' => '192.168.10.100',
            ],
            ['192.168.10.26']
        );

        $this->assertSame('203.0.113.1', $ip);
    }

    public function testTrustedProxiesFromEnvDefaultsToLoopback(): void
    {
        $previous = getenv('TRUSTED_PROXIES');
        putenv('TRUSTED_PROXIES');
        unset($_ENV['TRUSTED_PROXIES']);

        try {
            $this->assertSame(['127.0.0.1', '::1'], ClientIpResolver::trustedProxiesFromEnv());
        } finally {
            if ($previous === false) {
                putenv('TRUSTED_PROXIES');
            } else {
                putenv('TRUSTED_PROXIES=' . $previous);
                $_ENV['TRUSTED_PROXIES'] = $previous;
            }
        }
    }
}
