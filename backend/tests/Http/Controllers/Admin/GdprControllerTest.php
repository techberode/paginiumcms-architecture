<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Gdpr\GdprPseudonym;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Tests\Http\TestCase;

final class GdprControllerTest extends TestCase
{
    public function testExportJsonAndAuditEvent(): void
    {
        $this->loginAsAdminUser();
        $subject = $this->createTestUser(
            'gdpr-export-' . uniqid('', true) . '@example.com',
            null,
            'GDPR Export Subject'
        );

        $repo = $this->container()->get(\PaginiumCMS\Modules\Security\Services\UserRepository::class);
        $user = $repo->findByEmail($subject['email']);
        $this->assertNotNull($user);

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/users/' . rawurlencode($user->getId()) . '/gdpr/export')
        );

        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success'] ?? false);
        $this->assertSame($user->getId(), $payload['data']['export']['subjectUserId'] ?? null);

        $audit = $this->container()->get(SecurityAuditStore::class);
        $events = $audit->list(['type' => 'gdpr_export'], 5);
        $this->assertNotSame([], $events);
    }

    public function testAnonymizeRequiresConfirmAndRedactsUser(): void
    {
        $this->loginAsAdminUser();
        $subject = $this->createTestUser(
            'gdpr-anon-' . uniqid('', true) . '@example.com',
            null,
            'GDPR Anon Subject'
        );

        $repo = $this->container()->get(\PaginiumCMS\Modules\Security\Services\UserRepository::class);
        $user = $repo->findByEmail($subject['email']);
        $this->assertNotNull($user);

        $missingConfirm = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/users/' . rawurlencode($user->getId()) . '/gdpr/anonymize', [])
        );
        $this->assertSame(422, $missingConfirm->getStatusCode());

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/users/' . rawurlencode($user->getId()) . '/gdpr/anonymize', [
                'confirm' => true,
            ])
        );
        $this->assertSame(200, $response->getStatusCode());

        $fresh = $repo->findById($user->getId());
        $this->assertNotNull($fresh);
        $this->assertTrue(GdprPseudonym::isAnonymizedEmail($fresh->getEmail()));
        $this->assertNull($repo->findByEmail($subject['email']));

        $audit = $this->container()->get(SecurityAuditStore::class);
        $events = $audit->list(['type' => 'gdpr_anonymize'], 5);
        $this->assertNotSame([], $events);
    }

    public function testEditorCannotAccessGdprRoutes(): void
    {
        $userData = $this->createTestUser();
        $repo = $this->container()->get(\PaginiumCMS\Modules\Security\Services\UserRepository::class);
        $user = $repo->findByEmail($userData['email']);
        if ($user !== null) {
            $user->setRoles(['EDITOR']);
            $repo->save($user);
        }
        $this->loginTestUser($userData['email'], $userData['password']);
        if ($this->currentUser !== null) {
            $this->currentUser->setRoles(['EDITOR']);
        }

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/users/' . rawurlencode($user?->getId() ?? '') . '/gdpr/export')
        );

        $this->assertSame(403, $response->getStatusCode());
    }
}
