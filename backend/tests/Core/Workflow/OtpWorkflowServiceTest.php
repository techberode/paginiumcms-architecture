<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Workflow;

use PaginiumCMS\Tests\Http\TestCase;

class OtpWorkflowServiceTest extends TestCase
{
    public function testRegistrationOtpFlowWithDebugCode(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $this->enableWorkflows(['registrationOtpEnabled' => true]);

        $service = $this->app->getContainer()->get(\PaginiumCMS\Core\Workflow\Services\OtpWorkflowService::class);
        $email = 'otp_' . uniqid() . '@example.com';

        $started = $service->startRegistration($email, 'OTP User', 'StrongP@ssw0rd123!');
        $this->assertNotEmpty($started['challenge_id']);
        $this->assertArrayHasKey('debug_code', $started);

        $verified = $service->verifyRegistration($started['challenge_id'], (string) $started['debug_code']);
        $this->assertSame($email, $verified['user']['email']);
        $this->assertSame('OTP User', $verified['user']['name']);
    }

    public function testRegistrationOtpRejectsInvalidCode(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $this->enableWorkflows(['registrationOtpEnabled' => true]);

        $service = $this->app->getContainer()->get(\PaginiumCMS\Core\Workflow\Services\OtpWorkflowService::class);
        $email = 'otp_bad_' . uniqid() . '@example.com';
        $started = $service->startRegistration($email, 'Bad OTP', 'StrongP@ssw0rd123!');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Neplatný overovací kód');
        $service->verifyRegistration($started['challenge_id'], '000000');
    }

    public function testCommentApprovalOtpFlow(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $this->enableWorkflows(['commentApprovalOtpEnabled' => true]);

        $settings = $this->app->getContainer()->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class);
        $this->assertTrue($settings->group('workflows')['commentApprovalOtpEnabled'] ?? false);

        $login = $this->loginAsAdminUser();
        $editor = $this->app->getContainer()->get(\PaginiumCMS\Modules\Security\Services\UserRepository::class)
            ->findByEmail($login['email']);
        $this->assertNotNull($editor);

        $comments = $this->app->getContainer()->get(\PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface::class);
        $comment = new \PaginiumCMS\Modules\Comments\Models\Comment('article-slug', 'Reader', 'Needs approval');
        $comments->save($comment);

        $service = $this->app->getContainer()->get(\PaginiumCMS\Core\Workflow\Services\OtpWorkflowService::class);
        $this->assertTrue($service->isCommentApprovalOtpEnabled());
        $started = $service->startCommentApproval($editor, $comment->getId());
        $this->assertArrayHasKey('debug_code', $started);

        $verified = $service->verifyCommentApproval($started['challenge_id'], (string) $started['debug_code'], $editor);
        $this->assertSame('approved', $verified['comment']['status']);
    }

    public function testPublishApprovalOtpFlow(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $this->enableWorkflows(['publishApprovalOtpEnabled' => true]);

        $settings = $this->app->getContainer()->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class);
        $this->assertTrue($settings->group('workflows')['publishApprovalOtpEnabled'] ?? false);

        $login = $this->loginAsAdminUser();
        $editor = $this->app->getContainer()->get(\PaginiumCMS\Modules\Security\Services\UserRepository::class)
            ->findByEmail($login['email']);
        $this->assertNotNull($editor);

        $repo = $this->app->getContainer()->get(\PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface::class);
        $page = new \PaginiumCMS\Core\FlatFile\Models\Page();
        $page->setSlug('otp-page-' . uniqid());
        $page->setTitle('OTP Page');
        $page->setContent('Body');
        $page->setStatus('draft');
        $repo->save($page);

        $service = $this->app->getContainer()->get(\PaginiumCMS\Core\Workflow\Services\OtpWorkflowService::class);
        $this->assertTrue($service->isPublishApprovalOtpEnabled());
        $started = $service->startPublishApproval($editor, 'page', $page->getSlug());
        $this->assertArrayHasKey('debug_code', $started);

        $verified = $service->verifyPublishApproval($started['challenge_id'], (string) $started['debug_code'], $editor);
        $this->assertSame('published', $verified['status']);

        $saved = $repo->findBySlug($page->getSlug(), 'page');
        $this->assertNotNull($saved);
        $this->assertSame('published', $saved->getStatus());
    }

    public function testResendDoesNotResetVerifyAttempts(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $this->enableWorkflows([
            'registrationOtpEnabled' => true,
            'otpMaxAttempts' => 3,
            'otpMaxResends' => 3,
        ]);

        $service = $this->app->getContainer()->get(\PaginiumCMS\Core\Workflow\Services\OtpWorkflowService::class);
        $email = 'otp_resend_' . uniqid() . '@example.com';
        $started = $service->startRegistration($email, 'Resend User', 'StrongP@ssw0rd123!');

        for ($i = 0; $i < 2; $i++) {
            try {
                $service->verifyRegistration($started['challenge_id'], '000000');
                $this->fail('Expected invalid code exception');
            } catch (\RuntimeException $e) {
                $this->assertSame('Neplatný overovací kód', $e->getMessage());
            }
        }

        $resent = $service->resendRegistration($started['challenge_id']);
        $this->assertSame($started['challenge_id'], $resent['challenge_id']);

        try {
            $service->verifyRegistration($started['challenge_id'], '000000');
            $this->fail('Expected invalid code exception');
        } catch (\RuntimeException $e) {
            $this->assertSame('Neplatný overovací kód', $e->getMessage());
        }

        try {
            $service->verifyRegistration($started['challenge_id'], '000000');
            $this->fail('Expected max attempts exception');
        } catch (\RuntimeException $e) {
            $this->assertSame('Prekročený počet pokusov — požiadajte o nový kód', $e->getMessage());
        }
    }

    public function testResendRejectsAfterMaxResendCount(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $this->enableWorkflows(['registrationOtpEnabled' => true]);

        $service = $this->app->getContainer()->get(\PaginiumCMS\Core\Workflow\Services\OtpWorkflowService::class);
        $email = 'otp_max_resend_' . uniqid() . '@example.com';
        $started = $service->startRegistration($email, 'Max Resend', 'StrongP@ssw0rd123!');

        $service->resendRegistration($started['challenge_id']);
        $service->resendRegistration($started['challenge_id']);
        $service->resendRegistration($started['challenge_id']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Prekročený počet opätovných odoslaní kódu');
        $service->resendRegistration($started['challenge_id']);
    }
}
