<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Services\AuthenticationManager;
use PaginiumCMS\Modules\Security\Services\SessionManager;
use PaginiumCMS\Modules\Security\Services\PasswordPolicy;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Modules\Security\Exception\AuthenticationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthenticationManagerTest extends TestCase
{
    private AuthenticationManager $auth;
    /** @var UserRepository&MockObject */
    private UserRepository $userRepository;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        $_SESSION = [];

        $sessionManager = new SessionManager();
        $passwordPolicy = new PasswordPolicy();

        // Mock UserRepository
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->auth = new AuthenticationManager(
            $sessionManager,
            $passwordPolicy,
            $this->userRepository
        );

        $this->testUser = new User();
        $this->testUser->setEmail('test@example.com');
        $this->testUser->setPassword('StrongP@ssw0rd123!');
        $this->testUser->setName('Test User');
        $this->testUser->setRoles(['USER']);
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }

    public function testLoginSuccess(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($this->testUser);

        $user = $this->auth->login('test@example.com', 'StrongP@ssw0rd123!');

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertTrue($this->auth->isAuthenticated());
    }

    public function testLoginWithInvalidEmail(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('nonexistent@example.com')
            ->willReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Neplatný email alebo heslo');

        $this->auth->login('nonexistent@example.com', 'SomePassword123!');
    }

    public function testLoginWithInvalidPassword(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($this->testUser);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Neplatný email alebo heslo');

        $this->auth->login('test@example.com', 'WrongPassword123!');
    }

    public function testLogout(): void
    {
        // Najprv prihlásime
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($this->testUser);

        $this->auth->login('test@example.com', 'StrongP@ssw0rd123!');
        $this->assertTrue($this->auth->isAuthenticated());

        // Potom odhlásime
        $this->auth->logout();
        $this->assertFalse($this->auth->isAuthenticated());
        $this->assertNull($this->auth->getCurrentUser());
    }

    public function testGetCurrentUser(): void
    {
        $currentBefore = $this->auth->getCurrentUser();
        $this->assertNull($currentBefore);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($this->testUser);

        $this->auth->login('test@example.com', 'StrongP@ssw0rd123!');

        $user = $this->auth->getCurrentUser();
        $this->assertNotNull($user);
        $this->assertEquals('test@example.com', $user->getEmail());
    }

    public function testChangePasswordSuccess(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($user) {
                return $user->verifyPassword('NewStrongP@ssw0rd123!');
            }));

        $this->auth->changePassword(
            $this->testUser,
            'StrongP@ssw0rd123!',
            'NewStrongP@ssw0rd123!'
        );

        $this->addToAssertionCount(1);
    }

    public function testChangePasswordWithWrongOldPassword(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Staré heslo nie je správne');

        $this->auth->changePassword(
            $this->testUser,
            'WrongPassword123!',
            'NewStrongP@ssw0rd123!'
        );
    }

    public function testChangePasswordWithWeakNewPassword(): void
    {
        $this->expectException(\PaginiumCMS\Modules\Security\Exception\SecurityException::class);

        $this->auth->changePassword(
            $this->testUser,
            'StrongP@ssw0rd123!',
            'weak'
        );
    }

    public function testResetPasswordSuccess(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($this->testUser);

        $this->userRepository
            ->expects($this->once())
            ->method('saveResetToken')
            ->with($this->testUser, $this->isType('string'));

        $token = $this->auth->resetPassword('test@example.com');

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testResetPasswordWithNonExistentEmail(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('nonexistent@example.com')
            ->willReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Používateľ s týmto emailom neexistuje');

        $this->auth->resetPassword('nonexistent@example.com');
    }

    public function testVerifyResetTokenSuccess(): void
    {
        $token = 'valid_reset_token_1234567890abcdef';

        $this->userRepository
            ->expects($this->once())
            ->method('findByResetToken')
            ->with($token)
            ->willReturn($this->testUser);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($user) {
                return $user->verifyPassword('NewStrongP@ssw0rd123!');
            }));

        $this->userRepository
            ->expects($this->once())
            ->method('clearResetToken')
            ->with($this->testUser);

        $this->auth->verifyResetToken($token, 'NewStrongP@ssw0rd123!');

        $this->addToAssertionCount(1);
    }

    public function testVerifyResetTokenWithInvalidToken(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByResetToken')
            ->with('invalid_token')
            ->willReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Neplatný alebo expirovaný token');

        $this->auth->verifyResetToken('invalid_token', 'NewStrongP@ssw0rd123!');
    }

    public function testVerifyResetTokenWithWeakPassword(): void
    {
        $token = 'valid_reset_token_1234567890abcdef';

        $this->userRepository
            ->expects($this->once())
            ->method('findByResetToken')
            ->with($token)
            ->willReturn($this->testUser);

        $this->expectException(\PaginiumCMS\Modules\Security\Exception\SecurityException::class);

        $this->auth->verifyResetToken($token, 'weak');
    }

    public function testIsAuthenticated(): void
    {
        $this->assertFalse($this->auth->isAuthenticated());

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($this->testUser);

        $this->auth->login('test@example.com', 'StrongP@ssw0rd123!');

        $this->assertTrue($this->auth->isAuthenticated());
    }
}
