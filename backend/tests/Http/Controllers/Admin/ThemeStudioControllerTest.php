<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Http\Themes\Services\ThemeRegistry;
use PaginiumCMS\Tests\Http\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\UploadedFile;

final class ThemeStudioControllerTest extends TestCase
{
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    /** @var list<string> */
    private array $createdThemeIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdThemeIds as $id) {
            $this->removePersistedTheme($id);
        }
        $this->createdThemeIds = [];
        parent::tearDown();
    }

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
        $this->assertIsBool($payload['data']['hasThumbnail']);
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

    public function testSaveRequiresAuth(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/save', [
            'themeId' => 'studio-it88-tmp',
            'files' => $this->safeStudioFiles('studio-it88-tmp'),
        ]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUserRoleCannotSaveTheme(): void
    {
        $userData = $this->createTestUser();
        $this->loginTestUser($userData['email'], $userData['password']);

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/save', [
            'themeId' => 'studio-it88-tmp',
            'files' => $this->safeStudioFiles('studio-it88-tmp'),
        ]));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertDirectoryDoesNotExist($this->themePackageDir('studio-it88-tmp'));
    }

    public function testSaveWritesPackageWithoutActivating(): void
    {
        $this->loginAsSuperAdminUser();
        $id = 'studio-it88-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $this->createdThemeIds[] = $id;

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/save', [
            'themeId' => $id,
            'files' => $this->safeStudioFiles($id),
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success']);
        $this->assertFalse($payload['data']['blocked']);
        $this->assertSame($id, $payload['data']['themeId']);
        $this->assertContains('theme.json', $payload['data']['written']);
        $this->assertFileExists($this->themePackageDir($id) . '/templates/default.html');

        $record = $this->container()->get(ThemeRegistry::class)->get($id);
        $this->assertNotNull($record);
        $this->assertFalse($record->enabled);
    }

    public function testSaveHostileHtmlDoesNotWriteAndLeavesBundledTheme(): void
    {
        $this->loginAsSuperAdminUser();
        $bundled = (string) file_get_contents(
            $this->themePackageDir('clean-journal') . '/templates/default.html'
        );
        $id = 'studio-it88-' . substr(bin2hex(random_bytes(4)), 0, 8);

        $files = $this->safeStudioFiles($id);
        $files['templates/default.html'] = '<script>alert(1)</script>';

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/save', [
            'themeId' => $id,
            'files' => $files,
        ]));

        $this->assertSame(422, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertFalse($payload['success']);
        $this->assertTrue($payload['data']['blocked']);
        $this->assertSame([], $payload['data']['written']);
        $this->assertDirectoryDoesNotExist($this->themePackageDir($id));
        $this->assertSame(
            $bundled,
            file_get_contents($this->themePackageDir('clean-journal') . '/templates/default.html')
        );
    }

    public function testThumbnailRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/clean-journal/thumbnail')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testThumbnailRejectsNonPng(): void
    {
        $this->loginAsSuperAdminUser();
        $id = 'studio-it88-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $this->createdThemeIds[] = $id;

        $save = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/save', [
            'themeId' => $id,
            'files' => $this->safeStudioFiles($id),
        ]));
        $this->assertSame(200, $save->getStatusCode());

        $response = $this->handleRequest($this->thumbnailRequest($id, '<svg xmlns="http://www.w3.org/2000/svg"></svg>', 'image/svg+xml'));
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testThumbnailUploadAndGet(): void
    {
        $this->loginAsSuperAdminUser();
        $id = 'studio-it88-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $this->createdThemeIds[] = $id;

        $save = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/themes/save', [
            'themeId' => $id,
            'files' => $this->safeStudioFiles($id),
        ]));
        $this->assertSame(200, $save->getStatusCode());

        $png = $this->pngBytes();
        $uploaded = $this->handleRequest($this->thumbnailRequest($id, $png, 'image/png'));
        $this->assertSame(200, $uploaded->getStatusCode());
        $payload = $this->getJsonResponse($uploaded);
        $this->assertTrue($payload['success']);
        $this->assertTrue($payload['data']['hasThumbnail']);

        $get = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/' . $id . '/thumbnail')
        );
        $this->assertSame(200, $get->getStatusCode());
        $this->assertSame('image/png', $get->getHeaderLine('Content-Type'));
        $this->assertSame($png, (string) $get->getBody());
    }

    /**
     * @return array<string, string>
     */
    private function safeStudioFiles(string $id): array
    {
        $manifest = <<<JSON
{
  "manifestVersion": 1,
  "id": "{$id}",
  "name": "Studio temp",
  "version": "0.1.0",
  "slots": ["header", "main", "footer"],
  "templates": ["default"]
}
JSON;

        return [
            'theme.json' => $manifest,
            'templates/default.html' => "<body>\n  {{> header}}\n  <main>{{content}}</main>\n  {{> footer}}\n</body>\n",
            'partials/header.html' => '<header><a href="/">{{siteName}}</a></header>',
            'partials/footer.html' => '<footer><p>{{siteName}}</p></footer>',
            'assets/theme.css' => "body { margin: 0; }\n",
        ];
    }

    private function pngBytes(): string
    {
        $bytes = base64_decode(self::PNG_BASE64, true);
        $this->assertNotFalse($bytes);

        return $bytes;
    }

    private function thumbnailRequest(string $id, string $bytes, string $mime): \Psr\Http\Message\ServerRequestInterface
    {
        $stream = (new StreamFactory())->createStream($bytes);
        $uploadedFile = new UploadedFile($stream, 'preview.png', $mime, strlen($bytes), UPLOAD_ERR_OK);
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/admin/themes/' . $id . '/thumbnail')
            ->withUploadedFiles(['file' => $uploadedFile]);

        if ($this->currentUser !== null) {
            $request = $request->withAttribute('user', $this->currentUser);
        }

        return $request;
    }

    private function themePackageDir(string $id): string
    {
        return dirname(__DIR__, 4) . '/resources/views/themes/' . $id;
    }

    private function removePersistedTheme(string $id): void
    {
        $dir = $this->themePackageDir($id);
        if (is_dir($dir)) {
            $this->removeDir($dir);
        }
        $this->container()->get(ThemeRegistry::class)->remove($id);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
