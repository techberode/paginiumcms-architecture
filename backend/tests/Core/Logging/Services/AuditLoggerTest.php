<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Services\AuditLogger;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PHPUnit\Framework\TestCase;

class AuditLoggerTest extends TestCase
{
    private AuditLogger $auditLogger;
    private LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->auditLogger = new AuditLogger($this->mockLogger);
    }

    public function testLog(): void
    {
        $this->mockLogger
        ->expects($this->once())
        ->method('log')
        ->with(
            'INFO',
            'AUDIT: login - user/user_123',
            $this->callback(function ($context) {
                return isset($context['action']) &&
                $context['action'] === 'login' &&
                isset($context['target']) &&
                $context['target'] === 'user/user_123';
            })
        );

        $this->auditLogger->log('login', 'user/user_123', 'user_123');
    }

    public function testContentAccess(): void
    {
        $this->mockLogger
        ->expects($this->once())
        ->method('log');

        $this->auditLogger->contentAccess('user_123', 'pages/home.md', 'view');
    }

    public function testContentChange(): void
    {
        $this->mockLogger
        ->expects($this->once())
        ->method('log')
        ->with(
            'WARNING',
            'AUDIT: update - pages/home.md',
            $this->callback(function ($context) {
                return isset($context['details']['changes']) &&
                $context['details']['changes'] === ['title' => 'New Title'];
            })
        );

        $this->auditLogger->contentChange('user_123', 'pages/home.md', ['title' => 'New Title']);
    }

    public function testLogin(): void
    {
        $this->mockLogger
        ->expects($this->once())
        ->method('log')
        ->with('INFO', 'AUDIT: login - user/user_123', $this->anything());

        $this->auditLogger->login('user_123', true);
    }

    public function testLoginFailed(): void
    {
        $this->mockLogger
        ->expects($this->once())
        ->method('log')
        ->with('WARNING', 'AUDIT: login - user/user_123', $this->anything());

        $this->auditLogger->login('user_123', false);
    }

    public function testPasswordChange(): void
    {
        $this->mockLogger
        ->expects($this->once())
        ->method('log')
        ->with('WARNING', 'AUDIT: password_change - user/user_123', $this->anything());

        $this->auditLogger->passwordChange('user_123');
    }

    public function testTwoFactorEnable(): void
    {
        $this->mockLogger
        ->expects($this->once())
        ->method('log')
        ->with('INFO', 'AUDIT: 2fa_enable - user/user_123', $this->anything());

        $this->auditLogger->twoFactorEnable('user_123');
    }

    public function testTwoFactorDisable(): void
    {
        $this->mockLogger
        ->expects($this->once())
        ->method('log')
        ->with('WARNING', 'AUDIT: 2fa_disable - user/user_123', $this->anything());

        $this->auditLogger->twoFactorDisable('user_123');
    }

    public function testRoleChange(): void
    {
        $this->mockLogger
        ->expects($this->once())
        ->method('log')
        ->with(
            'WARNING',
            'AUDIT: role_change - user/user_123',
            $this->callback(function ($context) {
                return isset($context['details']['old_roles']) &&
                $context['details']['old_roles'] === ['USER'] &&
                isset($context['details']['new_roles']) &&
                $context['details']['new_roles'] === ['ADMIN'];
            })
        );

        $this->auditLogger->roleChange('user_123', ['USER'], ['ADMIN']);
    }

    public function testContentDelete(): void
    {
        $this->mockLogger
        ->expects($this->once())
        ->method('log')
        ->with('ERROR', 'AUDIT: delete - pages/home.md', $this->anything());

        $this->auditLogger->contentDelete('user_123', 'pages/home.md');
    }

    public function testSystem(): void
    {
        $this->mockLogger
        ->expects($this->once())
        ->method('log')
        ->with('INFO', 'SYSTEM: System started', $this->anything());

        $this->auditLogger->system('System started', 'INFO');
    }
}
