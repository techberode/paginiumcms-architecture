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
}
