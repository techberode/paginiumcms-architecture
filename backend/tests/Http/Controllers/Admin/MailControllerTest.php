<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class MailControllerTest extends TestCase
{
    public function testStatusRequiresAuth(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/mail'));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testStatusWhenImapDisabled(): void
    {
        $this->loginAsAdminUser();
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/mail'));
        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success'] ?? false);
        $this->assertFalse((bool) ($payload['data']['enabled'] ?? true));
    }

    public function testSendRequiresAuth(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/mail/send', [
            'to' => 'guest@example.com',
            'subject' => 'Hello',
            'body' => 'Hi',
        ]));
        $this->assertSame(401, $response->getStatusCode());
    }
}
