<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Firewall;

use PaginiumCMS\Core\Security\Firewall\FirewallRequestBodyReader;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

final class FirewallRequestBodyReaderTest extends TestCase
{
    private FirewallRequestBodyReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reader = new FirewallRequestBodyReader();
    }

    public function testReadsJsonBodyAndRewindsStream(): void
    {
        $stream = (new StreamFactory())->createStream('{"email":"a@b.com"}');
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/auth/login')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);

        $snapshot = $this->reader->read($request);

        $this->assertSame('{"email":"a@b.com"}', $snapshot);
        $this->assertSame('{"email":"a@b.com"}', (string) $request->getBody());
    }

    public function testSkipsMultipartUploads(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/media/upload')
            ->withHeader('Content-Type', 'multipart/form-data; boundary=abc');

        $this->assertNull($this->reader->read($request));
    }

    public function testSkipsGetRequests(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/health');

        $this->assertNull($this->reader->read($request));
    }
}
