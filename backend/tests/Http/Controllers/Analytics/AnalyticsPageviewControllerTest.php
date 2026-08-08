<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Analytics;

use PaginiumCMS\Tests\Http\TestCase;

final class AnalyticsPageviewControllerTest extends TestCase
{
    public function testPageviewBeaconRecordsVisit(): void
    {
        $body = json_encode(['uri' => '/about'], JSON_THROW_ON_ERROR);
        $request = $this->createJsonRequest('POST', '/api/analytics/pageview', null, [
            'Content-Type' => 'application/json',
            'User-Agent' => 'PaginiumCMS-Test/1.0',
        ]);
        $request = $request->withBody(
            (new \Slim\Psr7\Factory\StreamFactory())->createStream($body)
        );

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['tracked']);
    }

    public function testPageviewBeaconRejectsInvalidPath(): void
    {
        $body = json_encode(['uri' => '/api/admin/settings'], JSON_THROW_ON_ERROR);
        $request = $this->createJsonRequest('POST', '/api/analytics/pageview', null, [
            'Content-Type' => 'application/json',
        ]);
        $request = $request->withBody(
            (new \Slim\Psr7\Factory\StreamFactory())->createStream($body)
        );

        $response = $this->handleRequest($request);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testPageviewBeaconDedupesWithinWindow(): void
    {
        $body = json_encode(['uri' => '/dedupe-test'], JSON_THROW_ON_ERROR);
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'PaginiumCMS-Test/1.0',
        ];
        // Pageview dedupe keys on client IP + URI — both beacons must share the same REMOTE_ADDR.
        $server = ['REMOTE_ADDR' => '203.0.113.44'];

        $request1 = $this->createJsonRequest('POST', '/api/analytics/pageview', null, $headers, $server)
            ->withBody((new \Slim\Psr7\Factory\StreamFactory())->createStream($body));
        $request2 = $this->createJsonRequest('POST', '/api/analytics/pageview', null, $headers, $server)
            ->withBody((new \Slim\Psr7\Factory\StreamFactory())->createStream($body));

        $first = $this->getJsonResponse($this->handleRequest($request1));
        $second = $this->getJsonResponse($this->handleRequest($request2));

        $this->assertTrue($first['data']['tracked']);
        $this->assertTrue($second['data']['skipped']);
    }
}
