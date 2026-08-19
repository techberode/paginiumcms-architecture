<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Services\TwoFactorManager;
use PaginiumCMS\Modules\Security\Services\TOTPGenerator;
use PaginiumCMS\Modules\Security\Services\QRCodeGenerator;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Modules\Security\Services\SessionManager;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Exception\TwoFactorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TwoFactorManagerTest extends TestCase
{
    private TwoFactorManager $twoFactor;
    private TOTPGenerator $totp;
    private User $user;
    /** @var UserRepository&MockObject */
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        $_SESSION = [];

        $this->totp = new TOTPGenerator(30, 6, 'sha1');
        $qrCodeGenerator = new QRCodeGenerator();
        $this->userRepository = $this->createMock(UserRepository::class);
        $sessionManager = new SessionManager();

        $this->twoFactor = new TwoFactorManager(
            $this->totp,
            $qrCodeGenerator,
            $this->userRepository,
            $sessionManager
        );

        $this->user = new User();
        $this->user->setEmail('test@example.com');
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }

    public function testEnableTwoFactor(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->user);

        $secret = $this->twoFactor->enableTwoFactor($this->user);

        $this->assertNotEmpty($secret);
        $this->assertTrue($this->user->isTwoFactorEnabled());
        $this->assertEquals($secret, $this->user->getTwoFactorSecret());
    }

    public function testDisableTwoFactor(): void
    {
        $this->user->setTwoFactorEnabled(true);
        $this->user->setTwoFactorSecret('secret123');
        $this->user->setTwoFactorVerifiedAt(time());

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->user);

        $this->twoFactor->disableTwoFactor($this->user);

        $this->assertFalse($this->user->isTwoFactorEnabled());
        $this->assertNull($this->user->getTwoFactorSecret());
        $this->assertNull($this->user->getTwoFactorVerifiedAt());
    }

    public function testIsTwoFactorEnabled(): void
    {
        $this->assertFalse($this->twoFactor->isTwoFactorEnabled($this->user));

        $this->user->setTwoFactorEnabled(true);
        $this->assertTrue($this->twoFactor->isTwoFactorEnabled($this->user));
    }

    public function testVerifyCodeValid(): void
    {
        $secret = $this->totp->generateSecret();
        $this->user->setTwoFactorEnabled(true);
        $this->user->setTwoFactorSecret($secret);

        // Mock findByEmail – vráti používateľa
        $this->userRepository
        ->expects($this->once())
        ->method('findByEmail')
        ->with('test@example.com')
        ->willReturn($this->user);

        $this->userRepository
        ->expects($this->once())
        ->method('save')
        ->with($this->user);

        $code = $this->totp->getCurrentCode($secret);

        $this->assertTrue($this->twoFactor->verifyCode($this->user, $code));
        $this->assertNotNull($this->user->getTwoFactorVerifiedAt());
    }

    public function testVerifyCodeInvalid(): void
    {
        $secret = $this->totp->generateSecret();
        $this->user->setTwoFactorEnabled(true);
        $this->user->setTwoFactorSecret($secret);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($this->user);

        $this->assertFalse($this->twoFactor->verifyCode($this->user, '000000'));
        $this->assertNull($this->user->getTwoFactorVerifiedAt());
    }

    public function testVerifyCodeThrowsExceptionWhenNotEnabled(): void
    {
        // Používateľ má 2FA vypnutú
        $this->user->setTwoFactorEnabled(false);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($this->user);

        $this->expectException(TwoFactorException::class);
        $this->expectExceptionMessage('Dvojfaktorová autentifikácia nie je aktivovaná');

        $this->twoFactor->verifyCode($this->user, '123456');
    }

    public function testRequireValidCodePasses(): void
    {
        $secret = $this->totp->generateSecret();
        $this->user->setTwoFactorEnabled(true);
        $this->user->setTwoFactorSecret($secret);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($this->user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->user);

        $code = $this->totp->getCurrentCode($secret);

        $this->twoFactor->requireValidCode($this->user, $code);
        $this->addToAssertionCount(1);
    }

    public function testRequireValidCodeThrowsException(): void
    {
        $secret = $this->totp->generateSecret();
        $this->user->setTwoFactorEnabled(true);
        $this->user->setTwoFactorSecret($secret);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($this->user);

        $this->expectException(TwoFactorException::class);
        $this->expectExceptionMessage('Neplatný TOTP kód');

        $this->twoFactor->requireValidCode($this->user, '000000');
    }

    public function testGetQRCode(): void
    {
        $secret = $this->twoFactor->generateSecret();
        $qrCode = $this->twoFactor->getQRCode($secret, 'test@example.com');

        $this->assertNotEmpty($qrCode);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $qrCode);
    }

    public function testGetProvisioningUri(): void
    {
        $secret = $this->twoFactor->generateSecret();
        $uri = $this->twoFactor->getProvisioningUri($secret, 'test@example.com');

        $this->assertNotEmpty($uri);
        $this->assertStringContainsString('otpauth://totp/', $uri);
        $this->assertStringContainsString('PaginiumCMS', $uri);
        // Email môže byť URL-encoded ako test%40example.com
        $this->assertMatchesRegularExpression('/test(@|%40)example\.com/', $uri);
        $this->assertStringContainsString('secret=', $uri);
    }

    public function testGetCurrentCode(): void
    {
        $secret = $this->twoFactor->generateSecret();
        $code = $this->twoFactor->getCurrentCode($secret);

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
    }

    public function testGenerateSecret(): void
    {
        $secret = $this->twoFactor->generateSecret();

        $this->assertNotEmpty($secret);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testIsTotpVerified(): void
    {
        $this->assertFalse($this->twoFactor->isTotpVerified());

        // Potrebujeme aby session mal nastavené TOTP verified
        $secret = $this->totp->generateSecret();
        $this->user->setTwoFactorEnabled(true);
        $this->user->setTwoFactorSecret($secret);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($this->user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->user);

        $code = $this->totp->getCurrentCode($secret);
        $this->twoFactor->verifyCode($this->user, $code);

        $this->assertTrue($this->twoFactor->isTotpVerified());
    }

    public function testIsTwoFactorPassed(): void
    {
        // 2FA nie je aktivovaná – malo by vrátiť true
        $this->assertTrue($this->twoFactor->isTwoFactorPassed($this->user));

        // Aktivácia 2FA bez overenia – malo by vrátiť false
        $secret = $this->totp->generateSecret();
        $this->user->setTwoFactorEnabled(true);
        $this->user->setTwoFactorSecret($secret);
        $this->assertFalse($this->twoFactor->isTwoFactorPassed($this->user));

        // Overenie 2FA – malo by vrátiť true
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($this->user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->user);

        $code = $this->totp->getCurrentCode($secret);
        $this->twoFactor->verifyCode($this->user, $code);
        $this->assertTrue($this->twoFactor->isTwoFactorPassed($this->user));
    }
}
