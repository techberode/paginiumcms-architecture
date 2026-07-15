<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Contact;

use PaginiumCMS\Tests\Http\TestCase;

class ContactControllerTest extends TestCase
{
    public function testSubmitContactMessage(): void
    {
        $request = $this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Question',
            'message' => 'I would like to know more about your CMS.',
        ]);
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['data']['id']);
    }

    public function testAdminCanListMessages(): void
    {
        $submit = $this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Admin Test',
            'email' => 'admin-test@example.com',
            'message' => 'Message for admin inbox test.',
        ]);
        $this->handleRequest($submit);

        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $request = $this->createJsonRequest('GET', '/api/admin/messages');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertGreaterThanOrEqual(1, $data['data']['count']);
    }
}
