<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Auth;

use PaginiumCMS\Tests\Http\TestCase;

final class SsoControllerTest extends TestCase
{
    public function testProvidersEndpointReturnsEnvelope(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/auth/sso/providers')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('providers', $data['data']);
    }
}
