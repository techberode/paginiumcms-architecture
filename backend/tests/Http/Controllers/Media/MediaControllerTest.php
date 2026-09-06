<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Media;

use PaginiumCMS\Tests\Http\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\UploadedFile;

class MediaControllerTest extends TestCase
{
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function pngBytes(): string
    {
        $bytes = base64_decode(self::PNG_BASE64, true);
        $this->assertNotFalse($bytes);

        return $bytes;
    }

    public function testListMediaRequiresAuth(): void
    {
        $request = $this->createJsonRequest('GET', '/api/media');
        $response = $this->handleRequest($request);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testListMediaReturnsEmptyArray(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $request = $this->createJsonRequest('GET', '/api/media');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
    }

    public function testUploadMedia(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $pngBytes = $this->pngBytes();

        $stream = (new StreamFactory())->createStream($pngBytes);
        $uploadedFile = new UploadedFile(
            $stream,
            'test-upload.png',
            'image/png',
            strlen($pngBytes),
            UPLOAD_ERR_OK
        );

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/media/upload')
            ->withUploadedFiles(['file' => $uploadedFile])
            ->withParsedBody(['altText' => 'Controller test alt']);

        if ($this->currentUser !== null) {
            $request = $request->withAttribute('user', $this->currentUser);
        }

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('test-upload.png', $data['data']['fileName']);
        $this->assertSame('Controller test alt', $data['data']['altText']);
        $this->assertSame('image/png', $data['data']['mimeType']);
    }

    public function testUploadMediaWithoutFileReturns400(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/media/upload');
        if ($this->currentUser !== null) {
            $request = $request->withAttribute('user', $this->currentUser);
        }

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function testUpdateAndDeleteMedia(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $stream = (new StreamFactory())->createStream($this->pngBytes());
        $uploadedFile = new UploadedFile(
            $stream,
            'lifecycle.png',
            'image/png',
            strlen($this->pngBytes()),
            UPLOAD_ERR_OK
        );

        $uploadRequest = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/media/upload')
            ->withUploadedFiles(['file' => $uploadedFile])
            ->withParsedBody(['altText' => 'Before']);

        if ($this->currentUser !== null) {
            $uploadRequest = $uploadRequest->withAttribute('user', $this->currentUser);
        }

        $uploadResponse = $this->handleRequest($uploadRequest);
        $uploadData = $this->getJsonResponse($uploadResponse);
        $path = $uploadData['data']['path'] ?? null;
        $this->assertNotNull($path);

        $patchRequest = $this->createJsonRequest(
            'PATCH',
            '/api/media/' . rawurlencode($path),
            ['altText' => 'After patch']
        );
        $patchResponse = $this->handleRequest($patchRequest);
        $patchData = $this->getJsonResponse($patchResponse);

        $this->assertEquals(200, $patchResponse->getStatusCode());
        $this->assertTrue($patchData['success']);
        $this->assertSame('After patch', $patchData['data']['altText']);

        $deleteRequest = $this->createJsonRequest(
            'DELETE',
            '/api/media/' . rawurlencode($path)
        );
        $deleteResponse = $this->handleRequest($deleteRequest);
        $deleteData = $this->getJsonResponse($deleteResponse);

        $this->assertEquals(200, $deleteResponse->getStatusCode());
        $this->assertTrue($deleteData['success']);
    }

    public function testListFolders(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $request = $this->createJsonRequest('GET', '/api/media/folders');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertContains('', $data['data']);
    }

    public function testCreateFolderAndUploadIntoFolder(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $createRequest = $this->createJsonRequest('POST', '/api/media/folders', ['folder' => 'uploads']);
        $createResponse = $this->handleRequest($createRequest);
        $createData = $this->getJsonResponse($createResponse);

        $this->assertEquals(201, $createResponse->getStatusCode());
        $this->assertTrue($createData['success']);

        $stream = (new StreamFactory())->createStream($this->pngBytes());
        $uploadedFile = new UploadedFile(
            $stream,
            'folder-file.png',
            'image/png',
            strlen($this->pngBytes()),
            UPLOAD_ERR_OK
        );

        $uploadRequest = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/media/upload')
            ->withUploadedFiles(['file' => $uploadedFile])
            ->withParsedBody(['folder' => 'uploads', 'altText' => 'In folder']);

        if ($this->currentUser !== null) {
            $uploadRequest = $uploadRequest->withAttribute('user', $this->currentUser);
        }

        $uploadResponse = $this->handleRequest($uploadRequest);
        $uploadData = $this->getJsonResponse($uploadResponse);

        $this->assertEquals(201, $uploadResponse->getStatusCode());
        $this->assertSame('uploads', $uploadData['data']['folder']);

        $listRequest = $this->createJsonRequest('GET', '/api/media?folder=uploads');
        $listResponse = $this->handleRequest($listRequest);
        $listData = $this->getJsonResponse($listResponse);

        $paths = array_column($listData['data'], 'path');
        $this->assertContains($uploadData['data']['path'], $paths);
        $this->assertSame('uploads', $listData['data'][array_search($uploadData['data']['path'], $paths, true)]['folder'] ?? null);
    }

    public function testBulkDeleteMedia(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $paths = [];
        foreach (['a.png', 'b.png'] as $name) {
            $stream = (new StreamFactory())->createStream($this->pngBytes());
            $uploadedFile = new UploadedFile($stream, $name, 'image/png', strlen($this->pngBytes()), UPLOAD_ERR_OK);
            $uploadRequest = (new ServerRequestFactory())
                ->createServerRequest('POST', '/api/media/upload')
                ->withUploadedFiles(['file' => $uploadedFile]);

            if ($this->currentUser !== null) {
                $uploadRequest = $uploadRequest->withAttribute('user', $this->currentUser);
            }

            $uploadResponse = $this->handleRequest($uploadRequest);
            $uploadData = $this->getJsonResponse($uploadResponse);
            $paths[] = $uploadData['data']['path'];
        }

        $bulkRequest = $this->createJsonRequest('POST', '/api/media/bulk-delete', ['paths' => $paths]);
        $bulkResponse = $this->handleRequest($bulkRequest);
        $bulkData = $this->getJsonResponse($bulkResponse);

        $this->assertEquals(200, $bulkResponse->getStatusCode());
        $this->assertTrue($bulkData['success']);
        $this->assertSame(2, $bulkData['data']['deleted']);
    }

    public function testListStockTopics(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $request = $this->createJsonRequest('GET', '/api/media/stock-topics');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertNotEmpty($data['data']);
    }

    public function testListFormats(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $request = $this->createJsonRequest('GET', '/api/media/formats');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertContains('image/png', $data['data']['mimeTypes']);
        $this->assertStringContainsString('image/png', $data['data']['accept']);
    }

    public function testServeFileReturnsBinaryForUploadedImage(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $pngBytes = $this->pngBytes();
        $stream = (new StreamFactory())->createStream($pngBytes);
        $uploadedFile = new UploadedFile(
            $stream,
            'serve-me.png',
            'image/png',
            strlen($pngBytes),
            UPLOAD_ERR_OK
        );

        $uploadRequest = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/media/upload')
            ->withUploadedFiles(['file' => $uploadedFile]);

        if ($this->currentUser !== null) {
            $uploadRequest = $uploadRequest->withAttribute('user', $this->currentUser);
        }

        $uploadResponse = $this->handleRequest($uploadRequest);
        $uploadData = $this->getJsonResponse($uploadResponse);
        $path = $uploadData['data']['path'] ?? null;
        $this->assertNotNull($path);

        $serveRequest = $this->createJsonRequest('GET', '/api/media/file/' . $path);
        $serveResponse = $this->handleRequest($serveRequest);

        $this->assertEquals(200, $serveResponse->getStatusCode());
        $this->assertSame('image/png', $serveResponse->getHeaderLine('Content-Type'));
        $this->assertSame($pngBytes, (string) $serveResponse->getBody());
    }

    public function testOptimizeMediaRequiresAuth(): void
    {
        $request = $this->createJsonRequest('POST', '/api/media/media/test.png/optimize');
        $response = $this->handleRequest($request);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testOptimizeMediaReducesLargePng(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $image = imagecreatetruecolor(640, 480);
        $this->assertNotFalse($image);
        $color = imagecolorallocate($image, 20, 120, 220);
        $this->assertNotFalse($color);
        imagefilledrectangle($image, 0, 0, 639, 479, $color);
        ob_start();
        imagepng($image, null, 0);
        imagedestroy($image);
        $pngBytes = ob_get_clean();
        $this->assertGreaterThan(10_000, strlen($pngBytes));

        $stream = (new StreamFactory())->createStream($pngBytes);
        $uploadedFile = new UploadedFile(
            $stream,
            'large-photo.png',
            'image/png',
            strlen($pngBytes),
            UPLOAD_ERR_OK
        );

        $uploadRequest = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/media/upload')
            ->withUploadedFiles(['file' => $uploadedFile]);

        if ($this->currentUser !== null) {
            $uploadRequest = $uploadRequest->withAttribute('user', $this->currentUser);
        }

        $uploadResponse = $this->handleRequest($uploadRequest);
        $uploadData = $this->getJsonResponse($uploadResponse);
        $path = $uploadData['data']['path'] ?? null;
        $this->assertNotNull($path);

        $optimizeRequest = $this->createJsonRequest(
            'POST',
            '/api/media/' . rawurlencode($path) . '/optimize'
        );
        $optimizeResponse = $this->handleRequest($optimizeRequest);
        $optimizeData = $this->getJsonResponse($optimizeResponse);

        $this->assertEquals(200, $optimizeResponse->getStatusCode());
        $this->assertTrue($optimizeData['success']);
        $this->assertIsArray($optimizeData['data']);
        $this->assertLessThan($optimizeData['data']['beforeBytes'], $optimizeData['data']['afterBytes']);
        $this->assertGreaterThan(0, $optimizeData['data']['savedBytes']);
        $this->assertSame(640, $optimizeData['data']['width']);
        $this->assertSame(480, $optimizeData['data']['height']);
        $this->assertSame(640, $optimizeData['data']['beforeWidth']);
        $this->assertSame(480, $optimizeData['data']['beforeHeight']);
    }

    public function testOptimizeMediaWithResize(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $image = imagecreatetruecolor(800, 600);
        $this->assertNotFalse($image);
        $color = imagecolorallocate($image, 20, 120, 220);
        $this->assertNotFalse($color);
        imagefilledrectangle($image, 0, 0, 799, 599, $color);
        ob_start();
        imagepng($image, null, 0);
        imagedestroy($image);
        $pngBytes = ob_get_clean();
        $this->assertGreaterThan(10_000, strlen($pngBytes));

        $stream = (new StreamFactory())->createStream($pngBytes);
        $uploadedFile = new UploadedFile(
            $stream,
            'resize-me.png',
            'image/png',
            strlen($pngBytes),
            UPLOAD_ERR_OK
        );

        $uploadRequest = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/media/upload')
            ->withUploadedFiles(['file' => $uploadedFile]);

        if ($this->currentUser !== null) {
            $uploadRequest = $uploadRequest->withAttribute('user', $this->currentUser);
        }

        $uploadResponse = $this->handleRequest($uploadRequest);
        $uploadData = $this->getJsonResponse($uploadResponse);
        $path = $uploadData['data']['path'] ?? null;
        $this->assertNotNull($path);

        $infoRequest = $this->createJsonRequest('GET', '/api/media/' . rawurlencode($path) . '/image-info');
        $infoResponse = $this->handleRequest($infoRequest);
        $infoData = $this->getJsonResponse($infoResponse);
        $this->assertEquals(200, $infoResponse->getStatusCode());
        $this->assertSame(800, $infoData['data']['width']);
        $this->assertSame(600, $infoData['data']['height']);

        $optimizeRequest = $this->createJsonRequest(
            'POST',
            '/api/media/' . rawurlencode($path) . '/optimize',
            ['targetWidth' => 400]
        );
        $optimizeResponse = $this->handleRequest($optimizeRequest);
        $optimizeData = $this->getJsonResponse($optimizeResponse);

        $this->assertEquals(200, $optimizeResponse->getStatusCode());
        $this->assertTrue($optimizeData['success']);
        $this->assertSame(400, $optimizeData['data']['width']);
        $this->assertSame(300, $optimizeData['data']['height']);
    }

    public function testOptimizePreviewAndApply(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $image = imagecreatetruecolor(800, 600);
        $this->assertNotFalse($image);
        $color = imagecolorallocate($image, 20, 120, 220);
        $this->assertNotFalse($color);
        imagefilledrectangle($image, 0, 0, 799, 599, $color);
        ob_start();
        imagepng($image, null, 0);
        imagedestroy($image);
        $pngBytes = ob_get_clean();

        $stream = (new StreamFactory())->createStream($pngBytes);
        $uploadedFile = new UploadedFile(
            $stream,
            'preview-flow.png',
            'image/png',
            strlen($pngBytes),
            UPLOAD_ERR_OK
        );

        $uploadRequest = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/media/upload')
            ->withUploadedFiles(['file' => $uploadedFile]);

        if ($this->currentUser !== null) {
            $uploadRequest = $uploadRequest->withAttribute('user', $this->currentUser);
        }

        $uploadResponse = $this->handleRequest($uploadRequest);
        $uploadData = $this->getJsonResponse($uploadResponse);
        $path = $uploadData['data']['path'] ?? null;
        $this->assertNotNull($path);
        $beforeBytes = (int) ($uploadData['data']['sizeBytes'] ?? 0);

        $previewRequest = $this->createJsonRequest(
            'POST',
            '/api/media/' . rawurlencode($path) . '/optimize/preview',
            ['targetWidth' => 400]
        );
        $previewResponse = $this->handleRequest($previewRequest);
        $previewData = $this->getJsonResponse($previewResponse);

        $this->assertEquals(200, $previewResponse->getStatusCode());
        $this->assertTrue($previewData['success']);
        $token = $previewData['data']['previewToken'] ?? '';
        $this->assertNotSame('', $token);
        $this->assertLessThan($beforeBytes, (int) ($previewData['data']['afterBytes'] ?? $beforeBytes));

        $serveRequest = $this->createJsonRequest('GET', '/api/media/optimize-preview/' . $token);
        $serveResponse = $this->handleRequest($serveRequest);
        $this->assertEquals(200, $serveResponse->getStatusCode());
        $this->assertSame('image/png', $serveResponse->getHeaderLine('Content-Type'));

        $applyRequest = $this->createJsonRequest(
            'POST',
            '/api/media/' . rawurlencode($path) . '/optimize/apply',
            ['previewToken' => $token]
        );
        $applyResponse = $this->handleRequest($applyRequest);
        $applyData = $this->getJsonResponse($applyResponse);

        $this->assertEquals(200, $applyResponse->getStatusCode());
        $this->assertTrue($applyData['success']);
        $this->assertSame(400, $applyData['data']['width']);
        $this->assertLessThan($beforeBytes, (int) ($applyData['data']['afterBytes'] ?? $beforeBytes));
    }
}
