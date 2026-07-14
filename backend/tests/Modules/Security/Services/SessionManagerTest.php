<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Services\SessionManager;
use PaginiumCMS\Modules\Security\Models\User;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    private SessionManager $session;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        $_SESSION = [];

        $this->session = new SessionManager();
    }

    protected function tearDown(): void
    {
        session_destroy();
        parent::tearDown();
    }

    public function testSetAndGetUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setName('Test User');

        $this->session->setUser($user);

        $retrieved = $this->session->getUser();
        $this->assertNotNull($retrieved);
        $this->assertEquals('test@example.com', $retrieved->getEmail());
        $this->assertEquals('Test User', $retrieved->getName());
    }

    public function testIsAuthenticated(): void
    {
        $this->assertFalse($this->session->isAuthenticated());

        $user = new User();
        $user->setEmail('test@example.com');
        $this->session->setUser($user);

        $this->assertTrue($this->session->isAuthenticated());
    }

    public function testClearUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->session->setUser($user);

        $this->assertTrue($this->session->isAuthenticated());

        $this->session->clearUser();

        $this->assertFalse($this->session->isAuthenticated());
        $this->assertNull($this->session->getUser());
    }

    public function testTotpVerified(): void
    {
        $this->assertFalse($this->session->isTotpVerified());

        $this->session->setTotpVerified();
        $this->assertTrue($this->session->isTotpVerified());

        $this->session->clearTotpVerified();
        $this->assertFalse($this->session->isTotpVerified());
    }

    public function testSetAndGet(): void
    {
        $this->session->set('test_key', 'test_value');
        $this->assertEquals('test_value', $this->session->get('test_key'));

        $this->session->set('test_array', ['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $this->session->get('test_array'));

        $this->assertEquals('default', $this->session->get('non_existent', 'default'));
    }

    public function testRemove(): void
    {
        $this->session->set('test_key', 'test_value');
        $this->assertEquals('test_value', $this->session->get('test_key'));

        $this->session->remove('test_key');
        $this->assertNull($this->session->get('test_key'));
    }

    public function testRegenerate(): void
    {
        $oldSessionId = session_id();
        $this->session->regenerate();
        $newSessionId = session_id();

        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    public function testDestroy(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->session->setUser($user);

        $this->assertTrue($this->session->isAuthenticated());

        $this->session->destroy();

        $this->assertFalse($this->session->isAuthenticated());
        $this->assertEmpty($_SESSION);
    }
}
