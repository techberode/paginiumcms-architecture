<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http;

/**
 * End-to-end smoke tests: public site + admin workflows on a single Slim app instance.
 */
class ApplicationFlowTest extends TestCase
{
    public function testPublicApiEndpointsRespondWithJson(): void
    {
        $endpoints = [
            ['GET', '/api/test'],
            ['GET', '/api/navigation'],
            ['GET', '/api/pages'],
            ['GET', '/api/articles'],
            ['GET', '/api/settings/public'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $response = $this->handleRequest($this->createJsonRequest($method, $uri));
            $data = $this->getJsonResponse($response);

            $this->assertEquals(200, $response->getStatusCode(), "Expected 200 for {$method} {$uri}");
            $this->assertTrue($data['success'] ?? false, "Expected success for {$method} {$uri}");
        }
    }

    public function testContactToAdminInboxFlow(): void
    {
        $submit = $this->createJsonRequest('POST', '/api/contact', [
            'name' => 'Flow Test User',
            'email' => 'flow-test@example.com',
            'subject' => 'Integration',
            'message' => 'End-to-end contact form message for admin inbox.',
        ]);
        $submitResponse = $this->handleRequest($submit);
        $submitData = $this->getJsonResponse($submitResponse);

        $this->assertEquals(201, $submitResponse->getStatusCode());
        $this->assertTrue($submitData['success']);
        $this->assertNotEmpty($submitData['data']['id']);

        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $list = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/messages'));
        $listData = $this->getJsonResponse($list);

        $this->assertEquals(200, $list->getStatusCode());
        $this->assertTrue($listData['success']);
        $this->assertGreaterThanOrEqual(1, $listData['data']['count']);

        $messageId = $submitData['data']['id'];
        $bulk = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/messages/bulk', [
                'ids' => [$messageId],
                'action' => 'read',
            ])
        );
        $bulkData = $this->getJsonResponse($bulk);
        $this->assertEquals(200, $bulk->getStatusCode());
        $this->assertTrue($bulkData['success']);
        $this->assertSame(1, $bulkData['data']['succeeded']);
    }

    public function testCommentModerationFlow(): void
    {
        $submit = $this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => 'welcome-post',
            'author' => 'Reader',
            'email' => 'reader@example.com',
            'content' => 'Great article, thanks for sharing!',
        ]);
        $submitResponse = $this->handleRequest($submit);
        $submitData = $this->getJsonResponse($submitResponse);

        $this->assertContains($submitResponse->getStatusCode(), [200, 201]);
        $this->assertTrue($submitData['success']);
        $commentId = $submitData['data']['id'] ?? null;
        $this->assertNotEmpty($commentId);

        $public = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/comments?articleSlug=welcome-post')
        );
        $publicData = $this->getJsonResponse($public);
        $this->assertEquals(200, $public->getStatusCode());
        $this->assertTrue($publicData['success']);

        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $approve = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/admin/comments/' . $commentId, [
                'status' => 'approved',
            ])
        );
        $approveData = $this->getJsonResponse($approve);
        $this->assertEquals(200, $approve->getStatusCode());
        $this->assertTrue($approveData['success']);

        $bulk = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/comments/bulk-workflow', [
                'ids' => [$commentId],
                'action' => 'read',
            ])
        );
        $bulkData = $this->getJsonResponse($bulk);
        $this->assertEquals(200, $bulk->getStatusCode());
        $this->assertTrue($bulkData['success']);
        $this->assertSame(1, $bulkData['data']['succeeded']);
    }

    public function testAdminNavigationUpdateFlow(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $update = $this->createJsonRequest('PUT', '/api/admin/navigation', [
            'items' => [
                ['label' => 'Home', 'path' => '/', 'order' => 0],
                ['label' => 'Blog', 'path' => '/blog', 'order' => 1],
                ['label' => 'Contact', 'path' => '/contact', 'order' => 2],
            ],
        ]);
        $updateResponse = $this->handleRequest($update);
        $updateData = $this->getJsonResponse($updateResponse);

        $this->assertEquals(200, $updateResponse->getStatusCode());
        $this->assertTrue($updateData['success']);
        $this->assertSame('Contact', $updateData['data'][2]['label']);

        $public = $this->handleRequest($this->createJsonRequest('GET', '/api/navigation'));
        $publicData = $this->getJsonResponse($public);
        $this->assertEquals(200, $public->getStatusCode());
        $this->assertSame('Contact', $publicData['data'][2]['label']);
    }

    public function testProtectedAdminRoutesRequireAuth(): void
    {
        $protected = [
            ['GET', '/api/admin/messages'],
            ['GET', '/api/admin/comments'],
            ['PUT', '/api/admin/navigation', ['items' => []]],
            ['GET', '/api/media'],
        ];

        foreach ($protected as $entry) {
            [$method, $uri] = $entry;
            $body = $entry[2] ?? null;
            $response = $this->handleRequest($this->createJsonRequest($method, $uri, $body));

            $this->assertEquals(401, $response->getStatusCode(), "Expected 401 for {$method} {$uri}");
        }
    }
}
