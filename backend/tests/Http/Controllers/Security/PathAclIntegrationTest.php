<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Security;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\AclRepository;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Tests\Http\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\UploadedFile;

/**
 * HTTP integration tests for path-level ACL enforcement (audit S9 / ISS-055).
 */
final class PathAclIntegrationTest extends TestCase
{
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function tearDown(): void
    {
        $this->disablePathAcl();
        parent::tearDown();
    }

    public function testEditorCanMutateWhenPathAclDisabled(): void
    {
        $this->loginAsEditor();

        $slug = 'acl-open-' . uniqid();
        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/pages', [
                'title' => 'Open page',
                'slug' => $slug,
                'content' => '# Open',
                'status' => 'draft',
            ])
        );

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testEditorDeniedUpdateOnRestrictedPath(): void
    {
        $slug = 'acl-restricted-' . uniqid();

        $this->enablePathAcl([[
            'id' => 'finance-only-admin',
            'path' => 'content/pages/acl-restricted-*',
            'roles' => ['ADMIN'],
            'permissions' => [],
            'enabled' => true,
        ]]);

        $this->seedPage($slug, 'draft');

        $this->loginAsEditor();

        $response = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/pages/' . $slug, [
                'title' => 'Blocked update',
                'slug' => $slug,
                'content' => '# Blocked',
                'status' => 'draft',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('ACL denied', (string) ($data['error'] ?? ''));
    }

    public function testAnonymousGetReturns404ForRestrictedPublishedPage(): void
    {
        $slug = 'acl-hidden-' . uniqid();

        $this->enablePathAcl([[
            'id' => 'hidden-from-public',
            'path' => 'content/pages/acl-hidden-*',
            'roles' => ['EDITOR'],
            'permissions' => [],
            'enabled' => true,
        ]]);

        $this->loginAsAdminUser();
        $this->seedPage($slug, 'published');

        $this->currentUser = null;
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/pages/' . $slug)
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRestrictedPageHiddenFromPublicList(): void
    {
        $slug = 'acl-list-hidden-' . uniqid();

        $this->enablePathAcl([[
            'id' => 'list-hidden',
            'path' => 'content/pages/acl-list-hidden-*',
            'roles' => ['EDITOR'],
            'permissions' => [],
            'enabled' => true,
        ]]);

        $this->loginAsAdminUser();
        $this->seedPage($slug, 'published');

        $this->currentUser = null;
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/pages?page=1&per_page=100')
        );
        $data = $this->getJsonResponse($response);

        $this->assertTrue($data['success']);
        $slugs = array_map(
            static fn (array $item): string => (string) ($item['slug'] ?? ''),
            $data['data'] ?? []
        );
        $this->assertNotContains($slug, $slugs);
    }

    public function testSuperAdminBypassesPathAclOnUpdate(): void
    {
        $slug = 'acl-super-' . uniqid();

        $this->enablePathAcl([[
            'id' => 'super-bypass',
            'path' => 'content/pages/acl-super-*',
            'roles' => ['ADMIN'],
            'permissions' => [],
            'enabled' => true,
        ]]);

        $this->seedPage($slug, 'draft');
        $this->loginAsSuperAdmin();

        $response = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/pages/' . $slug, [
                'title' => 'Super update',
                'slug' => $slug,
                'content' => '# Super',
                'status' => 'draft',
            ])
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDraftSaveDeniedForRestrictedPath(): void
    {
        $slug = 'acl-draft-' . uniqid();

        $this->enablePathAcl([[
            'id' => 'draft-restricted',
            'path' => 'content/pages/acl-draft-*',
            'roles' => ['ADMIN'],
            'permissions' => [],
            'enabled' => true,
        ]]);

        $this->loginAsEditor();

        $response = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/drafts/page/' . $slug, [
                'title' => 'Draft blocked',
                'content' => '# Draft',
            ])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testMediaUploadDeniedInRestrictedFolder(): void
    {
        $this->enablePathAcl([[
            'id' => 'media-private',
            'path' => 'content/media/private/*',
            'roles' => ['ADMIN'],
            'permissions' => [],
            'enabled' => true,
        ]]);

        $this->loginAsEditor();

        $pngBytes = base64_decode(self::PNG_BASE64, true);
        $this->assertNotFalse($pngBytes);

        $stream = (new StreamFactory())->createStream($pngBytes);
        $uploadedFile = new UploadedFile(
            $stream,
            'private-upload.png',
            'image/png',
            strlen($pngBytes),
            UPLOAD_ERR_OK
        );

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/media/upload')
            ->withUploadedFiles(['file' => $uploadedFile])
            ->withParsedBody(['folder' => 'private', 'altText' => 'blocked']);

        if ($this->currentUser instanceof User) {
            $request = $request->withAttribute('user', $this->currentUser);
        }

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function testAdminCanUpdateRestrictedPathWhenRoleAllowed(): void
    {
        $slug = 'acl-admin-ok-' . uniqid();

        $this->enablePathAcl([[
            'id' => 'admin-allowed',
            'path' => 'content/pages/acl-admin-ok-*',
            'roles' => ['ADMIN'],
            'permissions' => [],
            'enabled' => true,
        ]]);

        $this->seedPage($slug, 'draft');
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/pages/' . $slug, [
                'title' => 'Admin update',
                'slug' => $slug,
                'content' => '# Admin',
                'status' => 'draft',
            ])
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @param list<array<string, mixed>> $rules
     */
    private function enablePathAcl(array $rules): void
    {
        $acl = $this->app->getContainer()->get(AclRepository::class);
        $acl->save(true, $rules);
    }

    private function disablePathAcl(): void
    {
        $acl = $this->app->getContainer()->get(AclRepository::class);
        $acl->save(false, []);
    }

    private function seedPage(string $slug, string $status): void
    {
        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        $page = new Page();
        $page->setSlug($slug);
        $page->setFrontMatter([
            'title' => 'ACL test ' . $slug,
            'slug' => $slug,
            'status' => $status,
        ]);
        $page->setContent("# ACL test\n");
        $repo->save($page);
    }

    /**
     * @return array{email: string, password: string, response: \Psr\Http\Message\ResponseInterface}
     */
    private function loginAsEditor(): array
    {
        $userData = $this->createTestUser();
        $repo = $this->app->getContainer()->get(UserRepository::class);
        $user = $repo->findByEmail($userData['email']);
        $this->assertNotNull($user);
        $user->setRoles(['EDITOR']);
        $repo->save($user);

        $login = $this->loginTestUser($userData['email'], $userData['password']);
        if ($this->currentUser instanceof User) {
            $this->currentUser->setRoles(['EDITOR']);
        }

        return array_merge($userData, $login);
    }

    /**
     * @return array{email: string, password: string, response: \Psr\Http\Message\ResponseInterface}
     */
    private function loginAsSuperAdmin(): array
    {
        $userData = $this->createTestUser();
        $repo = $this->app->getContainer()->get(UserRepository::class);
        $user = $repo->findByEmail($userData['email']);
        $this->assertNotNull($user);
        $user->setRoles(['SUPER_ADMIN']);
        $repo->save($user);

        $login = $this->loginTestUser($userData['email'], $userData['password']);
        if ($this->currentUser instanceof User) {
            $this->currentUser->setRoles(['SUPER_ADMIN']);
        }

        return array_merge($userData, $login);
    }
}
