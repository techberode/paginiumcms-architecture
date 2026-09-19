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
            'phonePrefix' => '+421',
            'phoneNumber' => '909554887',
            'subject' => 'Question',
            'message' => 'I would like to know more about your CMS.',
        ]);
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['data']['id']);
    }

    public function testSubmitRequiresRegisteredEmail(): void
    {
        $missing = $this->handleRequest($this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Jane Doe',
            'phonePrefix' => '+421',
            'phoneNumber' => '909554887',
            'message' => 'I would like to know more about your CMS.',
        ]));
        $this->assertSame(422, $missing->getStatusCode());

        $disposable = $this->handleRequest($this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Jane Doe',
            'email' => 'guest@mailinator.com',
            'phonePrefix' => '+421',
            'phoneNumber' => '909554887',
            'message' => 'I would like to know more about your CMS.',
        ]));
        $this->assertSame(422, $disposable->getStatusCode());
        $errors = $this->getJsonResponse($disposable)['errors'] ?? [];
        $this->assertArrayHasKey('email', $errors);
    }

    public function testAdminCanListMessages(): void
    {
        $submit = $this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Admin Test',
            'email' => 'admin-test@example.com',
            'phonePrefix' => '+421',
            'phoneNumber' => '909554887',
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

    public function testSecondContactWithSameSubjectAppendsToThread(): void
    {
        $email = 'thread-' . uniqid('', true) . '@example.com';
        $first = $this->handleRequest($this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Jane Doe',
            'email' => $email,
            'phonePrefix' => '+421',
            'phoneNumber' => '909554887',
            'subject' => 'Technická podpora',
            'message' => 'Prvá správa pre vlákno formulára.',
        ]));
        $this->assertSame(201, $first->getStatusCode());
        $firstId = $this->getJsonResponse($first)['data']['id'] ?? null;

        $second = $this->handleRequest($this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Jane Doe',
            'email' => $email,
            'phonePrefix' => '+421',
            'phoneNumber' => '909554887',
            'subject' => 'Technická podpora',
            'message' => 'Druhá správa dopĺňa rovnaké vlákno.',
        ]));
        $payload = $this->getJsonResponse($second);
        $this->assertSame(201, $second->getStatusCode());
        $this->assertTrue($payload['data']['appended'] ?? false);
        $this->assertSame($firstId, $payload['data']['id'] ?? null);

        $this->loginAsAdminUser();
        $inbox = $this->getJsonResponse($this->handleRequest($this->createJsonRequest('GET', '/api/admin/messages')));
        $match = null;
        foreach ($inbox['data']['items'] ?? [] as $item) {
            if (is_array($item) && ($item['id'] ?? null) === $firstId) {
                $match = $item;
                break;
            }
        }
        $this->assertIsArray($match);
        $this->assertCount(1, $match['thread'] ?? []);
    }

    public function testAdminCanClaimAndReplyOnDesk(): void
    {
        $submit = $this->handleRequest($this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Desk Guest',
            'email' => 'desk-guest-' . uniqid('', true) . '@example.com',
            'phonePrefix' => '+421',
            'phoneNumber' => '909554887',
            'subject' => 'Všeobecný dotaz',
            'message' => 'Potrebujem pomôcť s nastavením webu.',
        ]));
        $id = $this->getJsonResponse($submit)['data']['id'] ?? '';
        $this->assertNotSame('', $id);

        $this->loginAsAdminUser();
        $claimed = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/messages/' . rawurlencode((string) $id) . '/claim', []));
        $this->assertSame(200, $claimed->getStatusCode());
        $claimedBody = $this->getJsonResponse($claimed);
        $this->assertSame('in_progress', $claimedBody['data']['handleStatus'] ?? null);

        $replied = $this->handleRequest($this->createJsonRequest(
            'POST',
            '/api/admin/messages/' . rawurlencode((string) $id) . '/replies',
            ['body' => 'Ozveme sa ešte dnes poobede.']
        ));
        $this->assertSame(200, $replied->getStatusCode());
        $thread = $this->getJsonResponse($replied)['data']['thread'] ?? [];
        $this->assertIsArray($thread);
        $this->assertSame('staff', $thread[0]['authorType'] ?? null);
    }

    public function testSubmitRequiresE164Phone(): void
    {
        $missing = $this->handleRequest($this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Jane Doe',
            'email' => 'jane-phone@example.com',
            'message' => 'I would like to know more about your CMS.',
        ]));
        $this->assertSame(422, $missing->getStatusCode());
        $this->assertArrayHasKey('phone', $this->getJsonResponse($missing)['errors'] ?? []);

        $local = $this->handleRequest($this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Jane Doe',
            'email' => 'jane-phone@example.com',
            'phonePrefix' => '421',
            'phoneNumber' => '909554887',
            'message' => 'I would like to know more about your CMS.',
        ]));
        $this->assertSame(422, $local->getStatusCode());

        $ok = $this->handleRequest($this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Jane Doe',
            'email' => 'jane-phone@example.com',
            'phone' => '+421909554887',
            'message' => 'I would like to know more about your CMS.',
        ]));
        $this->assertSame(201, $ok->getStatusCode());

        $this->loginAsAdminUser();
        $inbox = $this->getJsonResponse($this->handleRequest($this->createJsonRequest('GET', '/api/admin/messages')));
        $match = null;
        foreach ($inbox['data']['items'] ?? [] as $item) {
            if (is_array($item) && ($item['email'] ?? null) === 'jane-phone@example.com') {
                $match = $item;
                break;
            }
        }
        $this->assertIsArray($match);
        $this->assertSame('+421909554887', $match['phone'] ?? null);
    }

    public function testAdminCanSaveMessageRouting(): void
    {
        $this->loginAsAdminUser();
        $saved = $this->handleRequest($this->createJsonRequest('PUT', '/api/admin/messages/routing', [
            'enabled' => true,
            'routes' => [[
                'subject' => 'Technická podpora',
                'enabled' => true,
                'teamIds' => [],
                'userIds' => [],
            ]],
        ]));
        $this->assertSame(200, $saved->getStatusCode());
        $read = $this->getJsonResponse($this->handleRequest($this->createJsonRequest('GET', '/api/admin/messages/routing')));
        $this->assertTrue($read['data']['enabled'] ?? false);
        $this->assertSame('Technická podpora', $read['data']['routes'][0]['subject'] ?? null);
    }
}
