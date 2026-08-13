<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Comments;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Models\Comment;
use PaginiumCMS\Tests\Http\TestCase;
use Slim\Psr7\Factory\StreamFactory;

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

    public function testApproveCommentWithOtpEnabled(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $this->enableWorkflows(['commentApprovalOtpEnabled' => true]);

        $settings = $this->app->getContainer()->get(SettingsRepositoryInterface::class);
        $this->assertTrue($settings->group('workflows')['commentApprovalOtpEnabled'] ?? false);

        $articleSlug = 'otp-comment-' . uniqid('', true);
        $submitRequest = $this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => $articleSlug,
            'author' => 'Reader',
            'email' => 'reader@example.com',
            'content' => 'Approve me with OTP',
        ]);
        $submitResponse = $this->handleRequest($submitRequest);
        $submitData = $this->getJsonResponse($submitResponse);
        $commentId = $submitData['data']['id'] ?? null;
        $this->assertNotNull($commentId);

        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $approveRequest = $this->createJsonRequest('PUT', '/api/admin/comments/' . $commentId, [
            'status' => Comment::STATUS_APPROVED,
        ]);
        $approveResponse = $this->handleRequest($approveRequest);
        $approveData = $this->getJsonResponse($approveResponse);

        $this->assertEquals(202, $approveResponse->getStatusCode());
        $this->assertTrue($approveData['requires_otp']);
        $this->assertArrayHasKey('debug_code', $approveData);

        $verifyRequest = $this->createJsonRequest('POST', '/api/admin/workflows/otp/verify', [
            'challenge_id' => $approveData['challenge_id'],
            'code' => $approveData['debug_code'],
        ]);
        $verifyResponse = $this->handleRequest($verifyRequest);
        $verifyData = $this->getJsonResponse($verifyResponse);

        $this->assertEquals(200, $verifyResponse->getStatusCode());
        $this->assertTrue($verifyData['success']);
        $this->assertSame(Comment::STATUS_APPROVED, $verifyData['comment']['status'] ?? null);
    }

    public function testApproveCommentUsesParsedBodyWhenStreamIsEmpty(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $this->enableWorkflows(['commentApprovalOtpEnabled' => true]);

        $articleSlug = 'otp-parsed-body-' . uniqid('', true);
        $submitResponse = $this->handleRequest($this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => $articleSlug,
            'author' => 'Reader',
            'email' => 'reader@example.com',
            'content' => 'Approve via parsed body',
        ]));
        $commentId = $this->getJsonResponse($submitResponse)['data']['id'] ?? null;
        $this->assertNotNull($commentId);

        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('PUT', '/api/admin/comments/' . $commentId, null);
        $request = $request->withBody((new StreamFactory())->createStream(''));
        $request = $request->withParsedBody(['status' => Comment::STATUS_APPROVED]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(202, $response->getStatusCode(), (string) json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->assertTrue($data['requires_otp']);
    }

    public function testHoneypotReturnsSilentSuccess(): void
    {
        $submitRequest = $this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => 'hp-comment-' . uniqid('', true),
            'author' => 'Bot',
            'content' => 'spam payload',
            '_hp' => 'filled',
        ]);
        $submitResponse = $this->handleRequest($submitRequest);
        $submitData = $this->getJsonResponse($submitResponse);

        $this->assertSame(201, $submitResponse->getStatusCode());
        $this->assertTrue($submitData['success']);
        $this->assertStringStartsWith('hp_', (string) ($submitData['data']['id'] ?? ''));
    }

    public function testObviousSpamIsRejected(): void
    {
        $submitRequest = $this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => 'spam-comment-' . uniqid('', true),
            'author' => 'Spammer',
            'email' => 'bot@mailinator.com',
            'content' => 'http://a.com http://b.com http://c.com http://d.com http://e.com buy now',
            '_hp' => '',
        ]);
        $submitResponse = $this->handleRequest($submitRequest);
        $submitData = $this->getJsonResponse($submitResponse);

        $this->assertSame(422, $submitResponse->getStatusCode());
        $this->assertFalse($submitData['success']);
    }
}
