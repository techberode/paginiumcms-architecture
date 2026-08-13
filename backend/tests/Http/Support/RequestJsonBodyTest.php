<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Support;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

final class RequestJsonBodyTest extends TestCase
{
    public function testPrefersParsedBodyOverEmptyStream(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody((new StreamFactory())->createStream(''));
        $request = $request->withParsedBody(['ref' => 'v2.1.0-beta.39']);

        $body = RequestJsonBody::decode($request);

        $this->assertSame(['ref' => 'v2.1.0-beta.39'], $body);
    }

    public function testFallsBackToRawBodyWhenParsedBodyMissing(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(
            (new StreamFactory())->createStream(JsonHelper::encode(['ref' => 'v2.1.0-beta.12']))
        );

        $body = RequestJsonBody::decode($request);

        $this->assertSame(['ref' => 'v2.1.0-beta.12'], $body);
    }

    public function testReturnsNullForInvalidJson(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/test');
        $request = $request->withBody((new StreamFactory())->createStream('not-json'));

        $this->assertNull(RequestJsonBody::decode($request));
    }
}
