<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Comments;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Models\Comment;
use PaginiumCMS\Tests\Http\TestCase;

class CommentsControllerTest extends TestCase
{
    public function testSubmitAndListApprovedComment(): void
    {
        $articleSlug = 'test-article-' . uniqid('', true);

        $submitRequest = $this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => $articleSlug,
            'author' => 'Reader',
            'email' => 'reader@example.com',
            'content' => 'Great article, thanks!',
        ]);
        $submitResponse = $this->handleRequest($submitRequest);
        $submitData = $this->getJsonResponse($submitResponse);

        $this->assertEquals(201, $submitResponse->getStatusCode());
        $this->assertTrue($submitData['success']);
        $this->assertSame(Comment::STATUS_PENDING, $submitData['data']['status'] ?? null);
        $commentId = $submitData['data']['id'] ?? null;
        $this->assertNotNull($commentId);

        $publicRequest = $this->createJsonRequest('GET', '/api/comments?articleSlug=' . urlencode($articleSlug));
        $publicResponse = $this->handleRequest($publicRequest);
        $publicData = $this->getJsonResponse($publicResponse);

        $this->assertEquals(200, $publicResponse->getStatusCode());
        $this->assertSame([], $publicData['data']);

        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $approveRequest = $this->createJsonRequest('PUT', '/api/admin/comments/' . $commentId, [
            'status' => Comment::STATUS_APPROVED,
        ]);
        $approveResponse = $this->handleRequest($approveRequest);
        $this->assertEquals(200, $approveResponse->getStatusCode());

        $publicAfterApprove = $this->handleRequest($publicRequest);
        $publicAfterData = $this->getJsonResponse($publicAfterApprove);
        $this->assertCount(1, $publicAfterData['data']);
    }

    public function testGuestCommentsDisabledBySetting(): void
    {
        $settings = $this->app->getContainer()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('comments', array_merge($settings->group('comments'), [
            'allowGuestComments' => false,
        ]));

        $request = $this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => 'blocked-guest-' . uniqid(),
            'author' => 'Guest',
            'content' => 'Should fail',
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Anonymné', (string) ($data['error'] ?? ''));

        $settings->setGroup('comments', array_merge($settings->group('comments'), [
            'allowGuestComments' => true,
        ]));
    }
}
