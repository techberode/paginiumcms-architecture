<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Media;

use PaginiumCMS\Tests\Http\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\UploadedFile;

class MediaControllerTest extends TestCase
{
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

        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
        $this->assertNotFalse($pngBytes);

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

        $stream = (new StreamFactory())->createStream('fake-png');
        $uploadedFile = new UploadedFile(
            $stream,
            'lifecycle.png',
            'image/png',
            8,
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

        $stream = (new StreamFactory())->createStream('fake-png');
        $uploadedFile = new UploadedFile(
            $stream,
            'folder-file.png',
            'image/png',
            8,
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
            $stream = (new StreamFactory())->createStream('fake-png');
            $uploadedFile = new UploadedFile($stream, $name, 'image/png', 8, UPLOAD_ERR_OK);
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
}
