<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Headless;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use PaginiumCMS\Modules\Security\Services\ApiJwtService;
use PaginiumCMS\Modules\Security\Services\ApiKeyStore;
use PaginiumCMS\Modules\Security\Services\ApiKeyVerifier;
use PaginiumCMS\Tests\Http\TestCase;

final class HeadlessApiIntegrationTest extends TestCase
{
    private function seedLegacyHomePage(): void
    {
        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        if ($repo->findBySlug('home', 'page') !== null) {
            return;
        }

        $fixture = DemoFixtures::seedFiles()['pages/home.md'] ?? null;
        $this->assertIsString($fixture);
        $writer = $this->app->getContainer()->get(FileWriterInterface::class);
        $writer->write('pages/home.md', $fixture, true);
    }

    /**
     * @param list<string> $scopes
     * @return array{token: string, id: string}
     */
    private function createKey(array $scopes, string $label = 'Headless test'): array
    {
        $store = $this->app->getContainer()->get(ApiKeyStore::class);
        $verifier = $this->app->getContainer()->get(ApiKeyVerifier::class);
        $created = $store->create($label, $scopes, null, 'test-admin', $verifier);

        return [
            'token' => $created['token'],
            'id' => $created['record']['id'],
        ];
    }

    /**
     * @return array{token: string, id: string}
     */
    private function createReadKey(): array
    {
        return $this->createKey(['content:read']);
    }

    public function testWriteKeyCanCreateDraftPage(): void
    {
        $key = $this->createKey(['content:write'], 'Writer');
        $slug = 'headless-' . bin2hex(random_bytes(4));

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/headless/pages', [
                'slug' => $slug,
                'title' => 'Headless draft',
                'content' => '# Headless',
                'status' => 'draft',
            ], [
                'Authorization' => 'Bearer ' . $key['token'],
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(201, $response->getStatusCode(), (string) json_encode($data));
        $this->assertTrue($data['success']);
        $this->assertSame($slug, $data['data']['slug'] ?? null);
    }

    public function testReadKeyCannotWrite(): void
    {
        $key = $this->createReadKey();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/headless/pages', [
                'slug' => 'blocked-write',
                'title' => 'Blocked',
                'content' => 'x',
                'status' => 'draft',
            ], [
                'Authorization' => 'Bearer ' . $key['token'],
            ])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testTokenIssueScopeReturnsShortLivedJwt(): void
    {
        $key = $this->createKey(['content:read', 'token:issue'], 'Issuer');

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/headless/token', [
                'scopes' => ['content:read'],
                'ttl' => 120,
            ], [
                'Authorization' => 'Bearer ' . $key['token'],
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $token = (string) ($data['data']['token'] ?? '');
        $this->assertNotSame('', $token);

        $jwtService = $this->app->getContainer()->get(ApiJwtService::class);
        $this->assertNotNull($jwtService->verify($token));

        $this->seedLegacyHomePage();
        $read = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/headless/pages/home', null, [
                'Authorization' => 'Bearer ' . $token,
            ])
        );
        $this->assertSame(200, $read->getStatusCode());
    }

    public function testAdminCanRotateApiKey(): void
    {
        $login = $this->loginAsSuperAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $create = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/platform/api-keys', [
                'label' => 'Rotate me',
                'scopes' => ['content:read'],
            ])
        );
        $created = $this->getJsonResponse($create);
        $id = $created['data']['key']['id'] ?? '';
        $oldToken = $created['data']['token'] ?? '';

        $rotate = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/platform/api-keys/' . $id . '/rotate')
        );
        $rotated = $this->getJsonResponse($rotate);

        $this->assertSame(200, $rotate->getStatusCode());
        $this->assertTrue($rotated['success']);
        $this->assertNotSame($oldToken, $rotated['data']['token'] ?? '');
    }

    public function testHeadlessPagesRequiresBearer(): void
    {
        $this->seedLegacyHomePage();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/headless/pages/home')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testValidApiKeyReadsPublishedPage(): void
    {
        $this->seedLegacyHomePage();
        $key = $this->createReadKey();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/headless/pages/home', null, [
                'Authorization' => 'Bearer ' . $key['token'],
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('home', $data['data']['slug'] ?? null);
    }

    public function testInvalidBearerOnPublicRouteReturns401WithoutFallback(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/pages/home', null, [
                'Authorization' => 'Bearer pgk_deadbeefdeadbeef_not-a-valid-secret-token',
            ])
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAdminCanCreateListAndRevokeApiKeys(): void
    {
        $login = $this->loginAsSuperAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $create = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/platform/api-keys', [
                'label' => 'Admin created',
                'scopes' => ['content:read'],
            ])
        );
        $created = $this->getJsonResponse($create);
        $this->assertSame(201, $create->getStatusCode());
        $this->assertTrue($created['success']);
        $this->assertStringStartsWith('pgk_', $created['data']['token'] ?? '');

        $list = $this->getJsonResponse(
            $this->handleRequest($this->createJsonRequest('GET', '/api/admin/platform/api-keys'))
        );
        $this->assertTrue($list['success']);
        $this->assertGreaterThan(0, count($list['data']['keys'] ?? []));

        $id = $created['data']['key']['id'] ?? '';
        $revoke = $this->handleRequest(
            $this->createJsonRequest('DELETE', '/api/admin/platform/api-keys/' . $id)
        );
        $this->assertSame(200, $revoke->getStatusCode());
    }
}
