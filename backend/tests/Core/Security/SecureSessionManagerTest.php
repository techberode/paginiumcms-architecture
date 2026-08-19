<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security;

use PaginiumCMS\Core\Security\SecureSessionManager;
use PaginiumCMS\Modules\Security\Models\User;
use PHPUnit\Framework\TestCase;

final class SecureSessionManagerTest extends TestCase
{
    private string|false|null $previousStrict = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousStrict = getenv('SESSION_STRICT');
        putenv('SESSION_STRICT=false');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        if ($this->previousStrict === false) {
            putenv('SESSION_STRICT');
        } elseif ($this->previousStrict === null) {
            putenv('SESSION_STRICT');
        } else {
            putenv('SESSION_STRICT=' . $this->previousStrict);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        parent::tearDown();
    }

    public function testTouchKeepsAuthenticatedSession(): void
    {
        $session = new SecureSessionManager();

        $user = new User();
        $user->setEmail('editor@example.com');
        $user->setName('Editor');
        $session->setUser($user);

        $this->assertTrue($session->isAuthenticated());

        $session->touch();

        $this->assertTrue($session->isAuthenticated());
        $this->assertNotNull($session->getUser());
    }

    public function testTouchReleasesSessionWriteLock(): void
    {
        $session = new SecureSessionManager();

        $user = new User();
        $user->setEmail('editor@example.com');
        $user->setName('Editor');
        $session->setUser($user);

        $this->assertTrue($session->isWriteLockReleased());

        $session->touch();

        $this->assertTrue($session->isWriteLockReleased());
        $this->assertTrue($session->isAuthenticated());
        $this->assertNotNull($session->getUser());
    }

    public function testStrictBindingDestroysSessionOnIpChange(): void
    {
        putenv('SESSION_STRICT=true');

        $_SERVER['REMOTE_ADDR'] = '192.168.10.26';
        $_SERVER['HTTP_USER_AGENT'] = 'TestAgent/1.0';

        $session = new SecureSessionManager();
        $user = new User();
        $user->setEmail('editor@example.com');
        $session->setUser($user);

        $_SERVER['REMOTE_ADDR'] = '192.168.10.99';

        $reloaded = new SecureSessionManager();

        $this->assertFalse($reloaded->ensureValid());
        $this->assertFalse($reloaded->isAuthenticated());
    }
}
