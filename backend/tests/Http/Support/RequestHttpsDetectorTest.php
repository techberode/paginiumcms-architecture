<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Support;

use PaginiumCMS\Http\Support\RequestHttpsDetector;
use PHPUnit\Framework\TestCase;

final class RequestHttpsDetectorTest extends TestCase
{
    public function testDetectsDirectHttps(): void
    {
        $result = RequestHttpsDetector::detect(['HTTPS' => 'on'], ['127.0.0.1']);
        $this->assertTrue($result['secure']);
        $this->assertSame('server_https', $result['source']);
    }

    public function testDetectsTrustedForwardedProto(): void
    {
        $server = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ];
        $result = RequestHttpsDetector::detect($server, ['127.0.0.1']);
        $this->assertTrue($result['secure']);
        $this->assertSame('x_forwarded_proto', $result['source']);
    }

    public function testIgnoresForwardedProtoFromUntrustedPeer(): void
    {
        $server = [
            'REMOTE_ADDR' => '203.0.113.50',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ];
        $result = RequestHttpsDetector::detect($server, ['127.0.0.1']);
        $this->assertFalse($result['secure']);
        $this->assertSame('untrusted_forwarded_proto', $result['source']);
    }
}
