<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

class CountsControllerTest extends TestCase
{
    public function testCountsForEditorIncludesContentCounts(): void
    {
        $userData = $this->createTestUser();
        $repo = $this->app->getContainer()->get(\PaginiumCMS\Modules\Security\Services\UserRepository::class);
        $user = $repo->findByEmail($userData['email']);
        $this->assertNotNull($user);
        $user->setRoles(['EDITOR']);
        $repo->save($user);

        $loginRequest = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $userData['email'],
            'password' => $userData['password'],
        ]);
        $this->assertEquals(200, $this->handleRequest($loginRequest)->getStatusCode());

        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/counts'));
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('pages', $data['data']);
        $this->assertArrayHasKey('articles', $data['data']);
        $this->assertArrayHasKey('media', $data['data']);
        $this->assertArrayNotHasKey('users', $data['data']);
    }

    public function testCountsForAdminIncludesAllModules(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/counts'));
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('comments', $data['data']);
        $this->assertArrayHasKey('messages', $data['data']);
        $this->assertArrayHasKey('newsletter', $data['data']);
        $this->assertArrayHasKey('trash', $data['data']);
        $this->assertArrayHasKey('users', $data['data']);
    }
}
