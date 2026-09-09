<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\ProjectPlanner;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Tests\Http\TestCase;
use PaginiumCMS\Tests\Support\TestStorageCleaner;

final class ProjectPlanControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->purgeQaPlans();
    }

    protected function tearDown(): void
    {
        $this->purgeQaPlans();
        parent::tearDown();
    }

    private function plansDirectory(): string
    {
        return TestStorageCleaner::contentRoot() . '/data/project-plans';
    }

    private function purgeQaPlans(): void
    {
        $dir = $this->plansDirectory();
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/qa-plan-*.json') ?: [] as $file) {
            @unlink($file);
        }
        foreach (['site-relaunch-2026', 'validation-plan', 'editor-plan', 'blocked-plan'] as $legacyId) {
            $path = $dir . '/' . $legacyId . '.json';
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function uniquePlanId(string $label = 'plan'): string
    {
        $safe = preg_replace('/[^a-z0-9]+/', '-', strtolower($label)) ?? 'plan';

        return 'qa-plan-' . trim($safe, '-') . '-' . bin2hex(random_bytes(3));
    }

    public function testListRequiresAuth(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/project-plans'));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUserRoleCannotReadPlans(): void
    {
        $userData = $this->createTestUser();
        $this->loginTestUser($userData['email'], $userData['password']);

        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/project-plans'));
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUserRoleCannotCreatePlan(): void
    {
        $userData = $this->createTestUser();
        $this->loginTestUser($userData['email'], $userData['password']);

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/project-plans', [
            'id' => 'blocked-plan',
            'title' => 'Should fail',
            'timezone' => 'UTC',
        ]));
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAdminCanCreateListShowUpdateItemsAndOverview(): void
    {
        $this->loginAsAdminUser();
        $planId = $this->uniquePlanId('relaunch');

        $create = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/project-plans', [
            'id' => $planId,
            'title' => 'Corporate relaunch',
            'timezone' => 'Europe/Bratislava',
            'isDefault' => true,
            'phases' => [
                ['id' => 'phase-1', 'title' => 'Content', 'sortOrder' => 1],
            ],
        ]));
        $created = $this->getJsonResponse($create);
        $this->assertSame(201, $create->getStatusCode(), (string) $create->getBody());
        $this->assertTrue($created['success'] ?? false);
        $this->assertSame($planId, $created['data']['id'] ?? null);
        $this->assertSame(0, $created['data']['progress']['percent'] ?? null);

        $list = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/project-plans'));
        $listed = $this->getJsonResponse($list);
        $this->assertSame(200, $list->getStatusCode());
        $planIds = [];
        foreach ($listed['data']['plans'] ?? [] as $row) {
            if (is_array($row) && isset($row['id']) && is_string($row['id'])) {
                $planIds[] = $row['id'];
            }
        }
        $this->assertContains($planId, $planIds);

        $show = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/project-plans/' . $planId)
        );
        $this->assertSame(200, $show->getStatusCode());

        $addItem = $this->handleRequest($this->createJsonRequest(
            'POST',
            '/api/admin/project-plans/' . $planId . '/items',
            [
                'id' => 'item-home',
                'phaseId' => 'phase-1',
                'title' => 'Homepage',
                'contentType' => 'page',
                'dueAt' => '2026-09-20T09:00:00+02:00',
                'status' => 'planned',
            ]
        ));
        $added = $this->getJsonResponse($addItem);
        $this->assertSame(201, $addItem->getStatusCode(), (string) $addItem->getBody());
        $this->assertCount(1, $added['data']['items'] ?? []);

        $patchItem = $this->handleRequest($this->createJsonRequest(
            'PATCH',
            '/api/admin/project-plans/' . $planId . '/items/item-home',
            ['status' => 'done']
        ));
        $patched = $this->getJsonResponse($patchItem);
        $this->assertSame(200, $patchItem->getStatusCode());
        $this->assertSame('done', $patched['data']['items'][0]['status'] ?? null);
        $this->assertNotEmpty($patched['data']['items'][0]['completedAt'] ?? null);
        $this->assertSame(100, $patched['data']['progress']['percent'] ?? null);

        $overview = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/project-plans/overview')
        );
        $kpis = $this->getJsonResponse($overview);
        $this->assertSame(200, $overview->getStatusCode());
        $this->assertGreaterThanOrEqual(1, $kpis['data']['planCount'] ?? 0);
        $this->assertArrayHasKey('percent', $kpis['data'] ?? []);

        $deleteItem = $this->handleRequest($this->createJsonRequest(
            'DELETE',
            '/api/admin/project-plans/' . $planId . '/items/item-home'
        ));
        $this->assertSame(200, $deleteItem->getStatusCode());
        $deleted = $this->getJsonResponse($deleteItem);
        $this->assertSame([], $deleted['data']['items'] ?? ['missing']);
    }

    public function testInvalidPlanIdIsRejected(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/project-plans', [
            'id' => '../etc/passwd',
            'title' => 'Hostile',
            'timezone' => 'UTC',
        ]));
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUnknownStatusReturns422(): void
    {
        $this->loginAsAdminUser();
        $planId = $this->uniquePlanId('validation');
        $this->handleRequest($this->createJsonRequest('POST', '/api/admin/project-plans', [
            'id' => $planId,
            'title' => 'Validation',
            'timezone' => 'UTC',
        ]));

        $response = $this->handleRequest($this->createJsonRequest(
            'POST',
            '/api/admin/project-plans/' . $planId . '/items',
            [
                'title' => 'Bad',
                'contentType' => 'page',
                'status' => 'published',
            ]
        ));
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testDisabledSettingHidesPlanner(): void
    {
        $this->loginAsAdminUser();
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('projectPlanner', ['enabled' => false]);

        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/project-plans'));
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testSuperAdminCanReadWithoutExplicitPermission(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/project-plans'));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testEditorWithDefaultPermissionsCanManage(): void
    {
        $userData = $this->createTestUser();
        $repo = $this->container()->get(UserRepository::class);
        $user = $repo->findByEmail($userData['email']);
        $this->assertNotNull($user);
        $user->setRoles(['EDITOR']);
        $repo->save($user);
        $this->loginTestUser($userData['email'], $userData['password']);
        if ($this->currentUser !== null) {
            $this->currentUser->setRoles(['EDITOR']);
        }

        $planId = $this->uniquePlanId('editor');
        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/project-plans', [
            'id' => $planId,
            'title' => 'Editor plan',
            'timezone' => 'UTC',
        ]));
        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
    }
}
