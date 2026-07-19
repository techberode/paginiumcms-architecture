<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Workflow;

use PaginiumCMS\Tests\Http\TestCase;

class OtpWorkflowServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        $storePath = __DIR__ . '/../../../storage/app/data/otp-challenges.json';
        if (is_file($storePath)) {
            @unlink($storePath);
        }

        parent::tearDown();
    }

    public function testRegistrationOtpFlowWithDebugCode(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $settings = $this->app->getContainer()->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class);
        $settings->setGroup('workflows', [
            'registrationOtpEnabled' => true,
            'otpTtlMinutes' => 15,
            'otpMaxAttempts' => 5,
        ]);

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

        $settings = $this->app->getContainer()->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class);
        $settings->setGroup('workflows', ['registrationOtpEnabled' => true]);

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

        $settings = $this->app->getContainer()->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class);
        $settings->setGroup('workflows', ['commentApprovalOtpEnabled' => true]);

        $login = $this->loginAsAdminUser();
        $editor = $this->app->getContainer()->get(\PaginiumCMS\Modules\Security\Services\UserRepository::class)
            ->findByEmail($login['email']);
        $this->assertNotNull($editor);

        $comments = $this->app->getContainer()->get(\PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface::class);
        $comment = new \PaginiumCMS\Modules\Comments\Models\Comment('article-slug', 'Reader', 'Needs approval');
        $comments->save($comment);

        $service = $this->app->getContainer()->get(\PaginiumCMS\Core\Workflow\Services\OtpWorkflowService::class);
        $started = $service->startCommentApproval($editor, $comment->getId());
        $this->assertArrayHasKey('debug_code', $started);

        $verified = $service->verifyCommentApproval($started['challenge_id'], (string) $started['debug_code'], $editor);
        $this->assertSame('approved', $verified['comment']['status']);
    }

    public function testPublishApprovalOtpFlow(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $settings = $this->app->getContainer()->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class);
        $settings->setGroup('workflows', ['publishApprovalOtpEnabled' => true]);

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
        $started = $service->startPublishApproval($editor, 'page', $page->getSlug());
        $this->assertArrayHasKey('debug_code', $started);

        $verified = $service->verifyPublishApproval($started['challenge_id'], (string) $started['debug_code'], $editor);
        $this->assertSame('published', $verified['status']);

        $saved = $repo->findBySlug($page->getSlug(), 'page');
        $this->assertNotNull($saved);
        $this->assertSame('published', $saved->getStatus());
    }
}
