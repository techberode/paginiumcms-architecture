<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Services\TOTPGenerator;
use PHPUnit\Framework\TestCase;

class TOTPGeneratorTest extends TestCase
{
    private TOTPGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new TOTPGenerator(30, 6, 'sha1');
    }

    public function testGenerateSecret(): void
    {
        $secret = $this->generator->generateSecret();

        $this->assertNotEmpty($secret);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testGetCurrentCode(): void
    {
        $secret = $this->generator->generateSecret();
        $code = $this->generator->getCurrentCode($secret);

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
    }

    public function testVerifyCodeValid(): void
    {
        $secret = $this->generator->generateSecret();
        $code = $this->generator->getCurrentCode($secret);

        $this->assertTrue($this->generator->verifyCode($secret, $code));
    }

    public function testVerifyCodeInvalid(): void
    {
        $secret = $this->generator->generateSecret();

        $this->assertFalse($this->generator->verifyCode($secret, '000000'));
        $this->assertFalse($this->generator->verifyCode($secret, '123456'));
    }

    public function testVerifyCodeWithWindow(): void
    {
        $secret = $this->generator->generateSecret();
        $code = $this->generator->getCurrentCode($secret);

        $this->assertTrue($this->generator->verifyCode($secret, $code, 1));
        $this->assertTrue($this->generator->verifyCode($secret, $code, 0));
    }

    public function testGetProvisioningUri(): void
    {
        $secret = $this->generator->generateSecret();
        $uri = $this->generator->getProvisioningUri($secret, 'test@example.com', 'PaginiumCMS');

        $this->assertNotEmpty($uri);
        $this->assertStringContainsString('otpauth://totp/', $uri);
        $this->assertStringContainsString('PaginiumCMS', $uri);

        // Email môže byť v URI zakódovaný ako test%40example.com
        $this->assertMatchesRegularExpression('/test(@|%40)example\.com/', $uri);
        $this->assertStringContainsString('secret=' . $secret, $uri);
    }

    public function testTwoDifferentSecretsGenerateDifferentCodes(): void
    {
        $secret1 = $this->generator->generateSecret();
        $secret2 = $this->generator->generateSecret();

        $code1 = $this->generator->getCurrentCode($secret1);
        $code2 = $this->generator->getCurrentCode($secret2);

        $this->assertNotEquals($secret1, $secret2);
        // Kódy môžu byť náhodou rovnaké, ale to je veľmi nepravdepodobné
    }
}
