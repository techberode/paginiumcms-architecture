<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Support;

use PaginiumCMS\Http\Support\BulkIdsParser;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

final class BulkIdsParserTest extends TestCase
{
    public function testFromRequestParsesStringIds(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/test')
            ->withBody((new StreamFactory())->createStream('{"ids":["a"," b ",""]}'));

        $this->assertSame(['a', 'b'], BulkIdsParser::fromRequest($request));
    }

    public function testFromRequestReturnsEmptyForInvalidBody(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/test')
            ->withBody((new StreamFactory())->createStream('not-json'));

        $this->assertSame([], BulkIdsParser::fromRequest($request));
    }
}
