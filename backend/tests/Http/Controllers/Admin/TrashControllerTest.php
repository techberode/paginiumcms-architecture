<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Tests\Http\TestCase;

class TrashControllerTest extends TestCase
{
    public function testListAndRestoreDeletedPage(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $slug = 'trash-test-' . uniqid();
        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        $page = new Page();
        $page->setSlug($slug);
        $page->setFrontMatter([
            'title' => 'Trash test',
            'slug' => $slug,
            'status' => 'draft',
        ]);
        $page->setContent("# Trash\n");
        $repo->save($page);

        $delete = $this->createJsonRequest('DELETE', '/api/pages/' . $slug);
        $deleteResponse = $this->handleRequest($delete);
        $this->assertEquals(200, $deleteResponse->getStatusCode());

        $list = $this->createJsonRequest('GET', '/api/admin/trash');
        $listResponse = $this->handleRequest($list);
        $listData = $this->getJsonResponse($listResponse);

        $this->assertEquals(200, $listResponse->getStatusCode());
        $this->assertTrue($listData['success']);
        $this->assertNotEmpty($listData['data']);

        $item = null;
        foreach ($listData['data'] as $entry) {
            if (str_contains((string) ($entry['originalPath'] ?? ''), $slug)) {
                $item = $entry;
                break;
            }
        }
        $this->assertNotNull($item, 'Deleted page must appear in trash list');

        $restore = $this->createJsonRequest('POST', '/api/admin/trash/' . $item['id'] . '/restore');
        $restoreResponse = $this->handleRequest($restore);
        $restoreData = $this->getJsonResponse($restoreResponse);

        $this->assertEquals(200, $restoreResponse->getStatusCode());
        $this->assertTrue($restoreData['success']);

        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        $page = $repo->findBySlug($slug, 'page');
        $this->assertInstanceOf(Page::class, $page);
    }

    public function testRestoreUnknownItemReturns404(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $request = $this->createJsonRequest('POST', '/api/admin/trash/nonexistent-id/restore');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function testBulkRestoreReturnsBatchSummary(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        $slug = 'bulk-trash-' . uniqid();
        $page = new Page();
        $page->setSlug($slug);
        $page->setFrontMatter([
            'title' => 'Bulk trash',
            'slug' => $slug,
            'status' => 'draft',
        ]);
        $page->setContent("# Trash bulk\n");
        $repo->save($page);

        $delete = $this->createJsonRequest('DELETE', '/api/pages/' . $slug);
        $this->assertSame(200, $this->handleRequest($delete)->getStatusCode());

        $list = $this->getJsonResponse($this->handleRequest($this->createJsonRequest('GET', '/api/admin/trash')));
        $item = null;
        foreach ($list['data'] as $entry) {
            if (str_contains((string) ($entry['originalPath'] ?? ''), $slug)) {
                $item = $entry;
                break;
            }
        }
        $this->assertNotNull($item);

        $bulk = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/trash/bulk-restore', [
                'ids' => [$item['id'], 'missing-id'],
            ])
        );
        $bulkData = $this->getJsonResponse($bulk);

        $this->assertSame(200, $bulk->getStatusCode());
        $this->assertTrue($bulkData['success']);
        $this->assertSame(2, $bulkData['data']['processed']);
        $this->assertSame(1, $bulkData['data']['succeeded']);
        $this->assertSame(1, $bulkData['data']['failed']);
    }

    public function testBulkPurgeAndEmptyTrash(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        $slug = 'purge-trash-' . uniqid();
        $page = new Page();
        $page->setSlug($slug);
        $page->setFrontMatter([
            'title' => 'Purge trash',
            'slug' => $slug,
            'status' => 'draft',
        ]);
        $page->setContent("# Purge\n");
        $repo->save($page);

        $delete = $this->createJsonRequest('DELETE', '/api/pages/' . $slug);
        $this->assertSame(200, $this->handleRequest($delete)->getStatusCode());

        $list = $this->getJsonResponse($this->handleRequest($this->createJsonRequest('GET', '/api/admin/trash')));
        $this->assertTrue($list['success'] ?? false);
        $item = null;
        foreach ($list['data'] ?? [] as $entry) {
            if (str_contains((string) ($entry['originalPath'] ?? ''), $slug)) {
                $item = $entry;
                break;
            }
        }
        $this->assertNotNull($item);

        $purge = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/trash/bulk-purge', [
                'ids' => [$item['id']],
            ])
        );
        $purgeData = $this->getJsonResponse($purge);
        $this->assertSame(200, $purge->getStatusCode());
        $this->assertTrue($purgeData['success']);
        $this->assertSame(1, $purgeData['data']['succeeded']);

        $empty = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/trash/empty'));
        $emptyData = $this->getJsonResponse($empty);
        $this->assertSame(200, $empty->getStatusCode());
        $this->assertTrue($emptyData['success']);
    }
}
