<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Services\CsrfProtectionManager;
use PaginiumCMS\Modules\Security\Services\SessionManager;
use PaginiumCMS\Modules\Security\Exception\SecurityException;
use PHPUnit\Framework\TestCase;

class CsrfProtectionManagerTest extends TestCase
{
    private CsrfProtectionManager $csrf;
    private SessionManager $session;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        $_SESSION = [];

        $this->session = new SessionManager();
        $this->csrf = new CsrfProtectionManager($this->session);
    }

    protected function tearDown(): void
    {
        session_destroy();
        parent::tearDown();
    }

    public function testGenerateToken(): void
    {
        $token = $this->csrf->generateToken('test_form');

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars

        // Overenie, že token je uložený v session
        $storedToken = $this->session->get('csrf_token_test_form');
        $this->assertEquals($token, $storedToken);
    }

    public function testGetTokenGeneratesNewIfNotExists(): void
    {
        $token1 = $this->csrf->getToken('test_form');
        $token2 = $this->csrf->getToken('test_form');

        // Mal by vrátiť rovnaký token, ak už existuje
        $this->assertEquals($token1, $token2);
    }

    public function testVerifyTokenValid(): void
    {
        $token = $this->csrf->generateToken('test_form');

        $this->assertTrue($this->csrf->verifyToken('test_form', $token));
    }

    public function testVerifyTokenInvalid(): void
    {
        $this->csrf->generateToken('test_form');

        $this->assertFalse($this->csrf->verifyToken('test_form', 'invalid_token'));
        $this->assertFalse($this->csrf->verifyToken('wrong_key', 'some_token'));
    }

    public function testRequireValidTokenPasses(): void
    {
        $token = $this->csrf->generateToken('test_form');

        // Nemalo by vyhodiť výnimku
        $this->csrf->requireValidToken('test_form', $token);
        $this->addToAssertionCount(1);

        // Token by mal byť po overení vymazaný
        $this->assertNull($this->session->get('csrf_token_test_form'));
    }

    public function testRequireValidTokenThrowsException(): void
    {
        $this->csrf->generateToken('test_form');

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Neplatný CSRF token');

        $this->csrf->requireValidToken('test_form', 'invalid_token');
    }

    public function testClearToken(): void
    {
        $this->csrf->generateToken('test_form');
        $this->assertNotNull($this->session->get('csrf_token_test_form'));

        $this->csrf->clearToken('test_form');
        $this->assertNull($this->session->get('csrf_token_test_form'));
    }

    public function testMultipleTokens(): void
    {
        $token1 = $this->csrf->generateToken('form1');
        $token2 = $this->csrf->generateToken('form2');

        $this->assertNotEquals($token1, $token2);

        $this->assertTrue($this->csrf->verifyToken('form1', $token1));
        $this->assertTrue($this->csrf->verifyToken('form2', $token2));
        $this->assertFalse($this->csrf->verifyToken('form1', $token2));
        $this->assertFalse($this->csrf->verifyToken('form2', $token1));
    }
}
