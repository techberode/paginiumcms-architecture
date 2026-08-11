<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Webhooks\Services\WebhookRegistryStore;
use PaginiumCMS\Core\Webhooks\WebhookEventCatalog;
use PaginiumCMS\Tests\Http\TestCase;

final class WebhookControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->purgeTestWebhooks();
    }

    protected function tearDown(): void
    {
        $this->purgeTestWebhooks();
        parent::tearDown();
    }

    public function testCreateReturnsCopyOnceSecretAndIndexHidesIt(): void
    {
        $this->loginAsAdminUser();

        $createResponse = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/platform/webhooks', [
                'label' => 'Test hook',
                'url' => 'https://hooks.example.com/paginium',
                'events' => [WebhookEventCatalog::CONTENT_PUBLISHED],
            ])
        );

        $this->assertSame(201, $createResponse->getStatusCode());
        $created = $this->getJsonResponse($createResponse);
        $this->assertTrue($created['success'] ?? false);
        $this->assertTrue($created['data']['copyOnce'] ?? false);
        $this->assertSame(64, strlen((string) ($created['data']['secret'] ?? '')));
        $createdId = (string) ($created['data']['webhook']['id'] ?? '');
        $this->assertNotSame('', $createdId);

        $indexResponse = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/platform/webhooks')
        );
        $index = $this->getJsonResponse($indexResponse);
        $webhooks = $index['data']['webhooks'] ?? [];

        $listed = null;
        foreach ($webhooks as $webhook) {
            if (is_array($webhook) && ($webhook['id'] ?? '') === $createdId) {
                $listed = $webhook;
                break;
            }
        }

        $this->assertIsArray($listed);
        $this->assertArrayNotHasKey('secret', $listed);
        $this->assertArrayNotHasKey('secretEnc', $listed);
    }

    public function testRequiresWebhooksManagePermission(): void
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
            $this->createJsonRequest('GET', '/api/admin/platform/webhooks')
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    private function purgeTestWebhooks(): void
    {
        $store = $this->container()->get(WebhookRegistryStore::class);

        foreach ($store->listMetadata() as $webhook) {
            $label = (string) ($webhook['label'] ?? '');
            $url = (string) ($webhook['url'] ?? '');

            if ($label !== 'Test hook' && !str_contains($url, 'hooks.example.com/paginium')) {
                continue;
            }

            try {
                $store->delete((string) $webhook['id']);
            } catch (\Throwable) {
                // Best-effort cleanup between test runs.
            }
        }
    }
}
