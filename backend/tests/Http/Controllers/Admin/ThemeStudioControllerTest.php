<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class ThemeStudioControllerTest extends TestCase
{
    public function testListFilesRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/clean-journal/files')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUserRoleCannotReadThemeFiles(): void
    {
        $userData = $this->createTestUser();
        $this->loginTestUser($userData['email'], $userData['password']);

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/clean-journal/files')
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSuperAdminCanListBundledThemeFiles(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/clean-journal/files')
        );
        $this->assertSame(200, $response->getStatusCode());

        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success']);
        $this->assertSame('clean-journal', $payload['data']['themeId']);
        $this->assertIsArray($payload['data']['files']);

        $paths = array_map(
            static fn (array $item): string => $item['relativePath'],
            $payload['data']['files']
        );
        $this->assertContains('theme.json', $paths);
        $this->assertContains('templates/default.html', $paths);
    }

    public function testSuperAdminCanReadThemeFile(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/clean-journal/file?path=theme.json')
        );
        $this->assertSame(200, $response->getStatusCode());

        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success']);
        $this->assertSame('theme.json', $payload['data']['relativePath']);
        $this->assertSame('json', $payload['data']['language']);
        $this->assertStringContainsString('"id": "clean-journal"', $payload['data']['content']);
    }

    public function testPathTraversalIsRejected(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest(
                'GET',
                '/api/admin/themes/clean-journal/file?path=' . rawurlencode('../../etc/passwd')
            )
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testMissingThemeReturns404(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/not-installed-theme/files')
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testMissingFileReturns404(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/clean-journal/file?path=templates/missing.html')
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testValidateRequiresAuth(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/validate', [
            'themeId' => 'clean-journal',
            'relativePath' => 'templates/default.html',
            'content' => '<main>{{content}}</main>',
        ]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUserRoleCannotValidateThemeBuffers(): void
    {
        $userData = $this->createTestUser();
        $this->loginTestUser($userData['email'], $userData['password']);

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/validate', [
            'themeId' => 'clean-journal',
            'relativePath' => 'templates/default.html',
            'content' => '<main>{{content}}</main>',
        ]));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testValidateAcceptsSafeHtml(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/validate', [
            'themeId' => 'clean-journal',
            'relativePath' => 'templates/default.html',
            'content' => "<body>\n  {{> header}}\n  <main>{{content}}</main>\n</body>\n",
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success']);
        $this->assertTrue($payload['data']['valid']);
        $this->assertSame([], $payload['data']['markers']);
    }

    public function testValidateRejectsHostileHtmlWithMarkers(): void
    {
        $this->loginAsSuperAdminUser();
        $onDisk = (string) file_get_contents(
            dirname(__DIR__, 4) . '/resources/views/themes/clean-journal/templates/default.html'
        );

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/validate', [
            'themeId' => 'clean-journal',
            'relativePath' => 'templates/default.html',
            'content' => "<img src=x onerror=alert(1)>\n<script>alert(1)</script>\n",
        ]));

        $this->assertSame(422, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertFalse($payload['success']);
        $this->assertFalse($payload['data']['valid']);
        $this->assertNotEmpty($payload['data']['markers']);
        $this->assertSame(
            $onDisk,
            file_get_contents(dirname(__DIR__, 4) . '/resources/views/themes/clean-journal/templates/default.html')
        );
    }

    public function testValidateRejectsCssJavascriptUrl(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/validate', [
            'themeId' => 'clean-journal',
            'relativePath' => 'assets/theme.css',
            'content' => 'body{background:url(javascript:alert(1));}',
        ]));

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testPreviewRequiresAuth(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/preview', [
            'themeId' => 'clean-journal',
            'files' => [
                'templates/default.html' => '<main>{{content}}</main>',
            ],
        ]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUserRoleCannotPreviewThemeBuffers(): void
    {
        $userData = $this->createTestUser();
        $this->loginTestUser($userData['email'], $userData['password']);

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/preview', [
            'themeId' => 'clean-journal',
            'files' => [
                'templates/default.html' => '<main>{{content}}</main>',
            ],
        ]));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPreviewReturnsSandboxedDocumentForSafeBuffers(): void
    {
        $this->loginAsSuperAdminUser();
        $onDisk = (string) file_get_contents(
            dirname(__DIR__, 4) . '/resources/views/themes/clean-journal/templates/default.html'
        );

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/preview', [
            'themeId' => 'clean-journal',
            'template' => 'templates/default.html',
            'files' => [
                'templates/default.html' => $onDisk,
                'partials/header.html' => '<header><a href="/">{{siteName}}</a></header>',
                'partials/footer.html' => '<footer><p>{{siteName}}</p></footer>',
                'assets/theme.css' => 'main { display: block; }',
            ],
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success']);
        $this->assertFalse($payload['data']['blocked']);
        $this->assertStringContainsString('script-src \'none\'', $payload['data']['document']);
        $this->assertStringContainsString('Sample content', $payload['data']['document']);
        $this->assertStringContainsString('main { display: block; }', $payload['data']['document']);
        $this->assertStringNotContainsString('<script', strtolower((string) $payload['data']['document']));
        $this->assertSame(
            $onDisk,
            file_get_contents(dirname(__DIR__, 4) . '/resources/views/themes/clean-journal/templates/default.html')
        );
    }

    public function testPreviewBlocksHostileHtmlAndDoesNotWriteDisk(): void
    {
        $this->loginAsSuperAdminUser();
        $path = dirname(__DIR__, 4) . '/resources/views/themes/clean-journal/templates/default.html';
        $onDisk = (string) file_get_contents($path);

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/preview', [
            'themeId' => 'clean-journal',
            'files' => [
                'templates/default.html' => "<img src=x onerror=alert(1)>\n<script>alert(1)</script>\n",
            ],
        ]));

        $this->assertSame(422, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertFalse($payload['success']);
        $this->assertTrue($payload['data']['blocked']);
        $this->assertSame('', $payload['data']['document']);
        $this->assertNotEmpty($payload['data']['issues']);
        $this->assertSame($onDisk, file_get_contents($path));
    }

    public function testNormalizeRequiresAuth(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/normalize', [
            'themeId' => 'untitled-theme',
            'html' => '<html><body><header>H</header><main>M</main><footer>F</footer></body></html>',
        ]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUserRoleCannotNormalizeThemeBuffers(): void
    {
        $userData = $this->createTestUser();
        $this->loginTestUser($userData['email'], $userData['password']);

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/normalize', [
            'themeId' => 'untitled-theme',
            'html' => '<html><body><header>H</header><main>M</main><footer>F</footer></body></html>',
        ]));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testNormalizeDropsScriptsAndDoesNotWriteDisk(): void
    {
        $this->loginAsSuperAdminUser();
        $path = dirname(__DIR__, 4) . '/resources/views/themes/clean-journal/templates/default.html';
        $onDisk = (string) file_get_contents($path);

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/normalize', [
            'themeId' => 'untitled-theme',
            'html' => '<html><body><header>H</header><main><script>alert(1)</script></main><footer>F</footer></body></html>',
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success']);
        $this->assertFalse($payload['data']['rejected']);
        $this->assertArrayHasKey('templates/default.html', $payload['data']['files']);
        $this->assertStringNotContainsString('<script', strtolower((string) $payload['data']['files']['templates/default.html']));
        $this->assertNotEmpty($payload['data']['dropped']);
        $this->assertSame($onDisk, file_get_contents($path));
    }

    public function testNormalizeRejectsPhpImport(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/normalize', [
            'themeId' => 'untitled-theme',
            'html' => '<html><body><?php echo 1; ?></body></html>',
        ]));

        $this->assertSame(422, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertFalse($payload['success']);
        $this->assertTrue($payload['data']['rejected']);
        $this->assertSame([], $payload['data']['files']);
    }
}
