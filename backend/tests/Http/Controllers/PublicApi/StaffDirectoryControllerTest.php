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
}
