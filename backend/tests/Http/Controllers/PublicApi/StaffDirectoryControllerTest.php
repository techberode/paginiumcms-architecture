<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\PublicApi;

use PaginiumCMS\Modules\Security\Services\UserProfileFields;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Tests\Http\TestCase;

final class StaffDirectoryControllerTest extends TestCase
{
    public function testPublicStaffOmitsPrivateFieldsUntilOptIn(): void
    {
        $this->loginAsAdminUser();
        $subject = $this->createTestUser(
            'staff-public-' . uniqid('', true) . '@example.com',
            null,
            'Public Ada'
        );
        $repo = $this->container()->get(UserRepository::class);
        $user = $repo->findByEmail($subject['email']);
        $this->assertNotNull($user);
        $user->setEmail('ada-private@example.com');
        $user->setPhone('+421900111222');
        $user->setPublish(UserProfileFields::normalizePublish([
            'contact' => true,
        ]));
        $repo->save($user);

        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/public/staff'));
        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success'] ?? false);
        $contacts = $payload['data']['contacts'] ?? [];
        $this->assertIsArray($contacts);
        $match = null;
        foreach ($contacts as $card) {
            if (is_array($card) && ($card['id'] ?? '') === $user->getId()) {
                $match = $card;
                break;
            }
        }
        $this->assertIsArray($match);
        $this->assertSame('Public Ada', $match['name'] ?? null);
        $this->assertArrayNotHasKey('email', $match);
        $this->assertArrayNotHasKey('phone', $match);
    }

    public function testPublicStaffQueryReturnsIslandCards(): void
    {
        $this->loginAsAdminUser();
        $subject = $this->createTestUser(
            'staff-query-' . uniqid('', true) . '@example.com',
            null,
            'Query Ada'
        );
        $repo = $this->container()->get(UserRepository::class);
        $user = $repo->findByEmail($subject['email']);
        $this->assertNotNull($user);
        $user->setPublish(UserProfileFields::normalizePublish(['contact' => true]));
        $user->setChatEnabled(true);
        $repo->save($user);

        $response = $this->handleRequest($this->createJsonRequest(
            'GET',
            '/api/public/staff?user=' . rawurlencode($user->getEmail())
        ));
        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $cards = $payload['data']['cards'] ?? [];
        $this->assertIsArray($cards);
        $this->assertCount(1, $cards);
        $this->assertSame($user->getId(), $cards[0]['id'] ?? null);
        $this->assertTrue($cards[0]['chatEnabled'] ?? false);
    }

    public function testStaffMessageRequiresChatEnabled(): void
    {
        $this->loginAsAdminUser();
        $subject = $this->createTestUser(
            'staff-chat-off-' . uniqid('', true) . '@example.com',
            null,
            'Silent Ada'
        );
        $repo = $this->container()->get(UserRepository::class);
        $user = $repo->findByEmail($subject['email']);
        $this->assertNotNull($user);
        $user->setPublish(UserProfileFields::normalizePublish(['contact' => true]));
        $repo->save($user);

        $denied = $this->handleRequest($this->createJsonRequest(
            'POST',
            '/api/public/staff/' . rawurlencode($user->getId()) . '/message',
            [
                'name' => 'Visitor',
                'email' => 'visitor@example.com',
                'message' => 'Hello, I need help with my account.',
            ]
        ));
        $this->assertSame(404, $denied->getStatusCode());

        $user->setChatEnabled(true);
        $repo->save($user);

        $accepted = $this->handleRequest($this->createJsonRequest(
            'POST',
            '/api/public/staff/' . rawurlencode($user->getId()) . '/message',
            [
                'name' => 'Visitor',
                'email' => 'visitor@example.com',
                'message' => 'Hello, I need help with my account.',
            ]
        ));
        $this->assertSame(201, $accepted->getStatusCode());
        $body = $this->getJsonResponse($accepted);
        $this->assertTrue($body['success'] ?? false);
        $this->assertNotEmpty($body['data']['id'] ?? null);

        $inbox = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/messages'));
        $this->assertSame(200, $inbox->getStatusCode());
        $list = $this->getJsonResponse($inbox);
        $match = null;
        foreach ($list['data']['items'] ?? [] as $item) {
            if (is_array($item) && ($item['id'] ?? '') === ($body['data']['id'] ?? null)) {
                $match = $item;
                break;
            }
        }
        $this->assertIsArray($match);
        $this->assertSame('staff-chat', $match['channel'] ?? null);
        $this->assertSame($user->getId(), $match['staffUserId'] ?? null);
    }
}
