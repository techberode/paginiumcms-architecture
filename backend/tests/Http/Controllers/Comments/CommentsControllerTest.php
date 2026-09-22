<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Comments;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Models\Comment;
use PaginiumCMS\Modules\Messages\Services\DeskInboxService;
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

    public function testStaffCanReplyOnApprovedComment(): void
    {
        $articleSlug = 'desk-article-' . uniqid('', true);
        $submit = $this->handleRequest($this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => $articleSlug,
            'author' => 'Reader',
            'email' => 'reader@example.com',
            'content' => 'Please clarify the second paragraph.',
        ]));
        $commentId = $this->getJsonResponse($submit)['data']['id'] ?? null;
        $this->assertNotNull($commentId);

        $this->loginAsAdminUser();
        $this->handleRequest($this->createJsonRequest('PUT', '/api/admin/comments/' . $commentId, [
            'status' => Comment::STATUS_APPROVED,
        ]));

        $reply = $this->handleRequest($this->createJsonRequest('POST', '/api/comments/' . $commentId . '/reply', [
            'content' => 'The second paragraph is about the public API contract.',
        ]));
        $this->assertSame(201, $reply->getStatusCode());

        $public = $this->getJsonResponse($this->handleRequest(
            $this->createJsonRequest('GET', '/api/comments?articleSlug=' . urlencode($articleSlug))
        ));
        $this->assertIsArray($public['data']);
        $this->assertCount(1, $public['data']);
        $this->assertCount(1, $public['data'][0]['replies'] ?? []);
        $this->assertTrue($public['data'][0]['replies'][0]['staffReply'] ?? false);

        $desk = $this->getJsonResponse($this->handleRequest($this->createJsonRequest('GET', '/api/auth/me/desk')));
        $this->assertTrue($desk['success'] ?? false);
        $this->assertIsArray($desk['data']);
        $this->assertArrayHasKey('deskCount', $desk['data']);
        $this->assertArrayHasKey('items', $desk['data']);
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
            'email' => 'guest@example.com',
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

    public function testDiscussionRatingRequiredWhenEnabled(): void
    {
        $settings = $this->app->getContainer()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('comments', array_merge($settings->group('comments'), [
            'enabled' => true,
            'requireApproval' => false,
            'allowGuestComments' => true,
            'ratingEnabled' => true,
        ]));

        $slug = 'rated-' . uniqid('', true);
        $missing = $this->handleRequest($this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => $slug,
            'author' => 'Guest',
            'email' => 'guest-rate@example.com',
            'content' => 'The article helped me set up SMTP.',
        ]));
        $this->assertSame(422, $missing->getStatusCode());

        $ok = $this->handleRequest($this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => $slug,
            'author' => 'Guest',
            'email' => 'guest-rate@example.com',
            'content' => 'The article helped me set up SMTP.',
            'rating' => 5,
        ]));
        $this->assertSame(201, $ok->getStatusCode());
        $this->assertSame(5, $this->getJsonResponse($ok)['data']['rating'] ?? null);

        $settings->setGroup('comments', array_merge($settings->group('comments'), [
            'ratingEnabled' => false,
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

        $this->enableWorkflows(['commentApprovalOtpEnabled' => true]);
        $this->assertTrue(
            $this->app->getContainer()->get(\PaginiumCMS\Core\Workflow\Services\OtpWorkflowService::class)->isCommentApprovalOtpEnabled(),
            'commentApprovalOtpEnabled must stay enabled after login'
        );

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

        $settings = $this->app->getContainer()->get(SettingsRepositoryInterface::class);
        $this->assertTrue(
            (bool) ($settings->group('workflows')['commentApprovalOtpEnabled'] ?? false),
            'commentApprovalOtpEnabled must be enabled before approve'
        );

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

        // Guard against settings reload flakes between login and approve.
        $this->enableWorkflows(['commentApprovalOtpEnabled' => true]);
        $this->assertTrue(
            (bool) ($settings->group('workflows')['commentApprovalOtpEnabled'] ?? false),
            'commentApprovalOtpEnabled must stay enabled after login'
        );

        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest(
            'PUT',
            '/api/admin/comments/' . $commentId
        );
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody((new StreamFactory())->createStream(''));
        $request = $request->withParsedBody(['status' => Comment::STATUS_APPROVED]);
        if ($this->currentUser !== null) {
            $request = $request->withAttribute('user', $this->currentUser);
        }

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(
            202,
            $response->getStatusCode(),
            'Expected OTP challenge (202); got '
            . $response->getStatusCode()
            . ' body='
            . json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $this->assertTrue($data['requires_otp'] ?? false);
    }

    public function testSubmitRequiresRegisteredEmail(): void
    {
        $missing = $this->handleRequest($this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => 'mail-comment-' . uniqid('', true),
            'author' => 'Reader',
            'content' => 'Great article, thanks!',
        ]));
        $this->assertSame(422, $missing->getStatusCode());

        $disposable = $this->handleRequest($this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => 'mail-comment-' . uniqid('', true),
            'author' => 'Reader',
            'email' => 'guest@mailinator.com',
            'content' => 'Great article, thanks!',
        ]));
        $this->assertSame(422, $disposable->getStatusCode());
        $errors = $this->getJsonResponse($disposable)['errors'] ?? [];
        $this->assertArrayHasKey('email', $errors);
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

    public function testBulkProcessedMarksHandleStatusDoneAndClearsDeskQueue(): void
    {
        $articleSlug = 'bulk-processed-' . uniqid('', true);
        $submit = $this->handleRequest($this->createJsonRequest('POST', '/api/comments', [
            'articleSlug' => $articleSlug,
            'author' => 'Reader',
            'email' => 'reader@example.com',
            'content' => 'Needs handling',
        ]));
        $commentId = $this->getJsonResponse($submit)['data']['id'] ?? null;
        $this->assertNotNull($commentId);

        $this->loginAsAdminUser();
        $this->handleRequest($this->createJsonRequest('PUT', '/api/admin/comments/' . $commentId, [
            'status' => Comment::STATUS_APPROVED,
        ]));

        $desk = $this->container()->get(DeskInboxService::class);
        $admin = $this->currentUser;
        $this->assertNotNull($admin);
        $before = $desk->items($admin);
        $this->assertTrue(
            array_any($before, static fn (array $row): bool => ($row['kind'] ?? '') === 'comment' && ($row['id'] ?? '') === $commentId)
        );

        $bulk = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/comments/bulk-workflow', [
            'ids' => [$commentId],
            'action' => 'processed',
        ]));
        $this->assertSame(200, $bulk->getStatusCode());

        $repo = $this->container()->get(\PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface::class);
        $saved = $repo->findById($commentId);
        $this->assertNotNull($saved);
        $this->assertSame('done', $saved->getHandleStatus());
        $this->assertTrue($saved->isRead());

        $after = $desk->items($admin);
        $this->assertFalse(
            array_any($after, static fn (array $row): bool => ($row['kind'] ?? '') === 'comment' && ($row['id'] ?? '') === $commentId)
        );
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
