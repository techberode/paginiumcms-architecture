<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Notification;

use PaginiumCMS\Core\Notification\Adapters\NtfyAdapter;
use PHPUnit\Framework\TestCase;

final class NtfyAdapterTest extends TestCase
{
    public function testBuildAuthHeadersNone(): void
    {
        $adapter = new NtfyAdapter('https://ntfy.sh', 'public-topic', 'none');

        $this->assertSame([], $adapter->buildAuthHeaders());
    }

    public function testBuildAuthHeadersBearerToken(): void
    {
        $adapter = new NtfyAdapter('https://ntfy.sh', 'private-topic', 'token', 'secret-token');

        $this->assertSame(['Authorization: Bearer secret-token'], $adapter->buildAuthHeaders());
    }

    public function testBuildAuthHeadersBasic(): void
    {
        $adapter = new NtfyAdapter('https://ntfy.local', 'alerts', 'basic', '', 'admin', 's3cret');

        $this->assertSame(
            ['Authorization: Basic ' . base64_encode('admin:s3cret')],
            $adapter->buildAuthHeaders()
        );
    }

    public function testBuildAuthHeadersTokenModeWithoutTokenIsEmpty(): void
    {
        $adapter = new NtfyAdapter('https://ntfy.sh', 'topic', 'token', '');

        $this->assertSame([], $adapter->buildAuthHeaders());
    }
}
