<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Services\PasswordPolicy;
use PaginiumCMS\Modules\Security\Exception\SecurityException;
use PHPUnit\Framework\TestCase;

class PasswordPolicyTest extends TestCase
{
    private PasswordPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new PasswordPolicy(
            minLength: 8,
            maxLength: 72,
            requireUppercase: true,
            requireLowercase: true,
            requireNumbers: true,
            requireSpecialChars: true
        );
    }

    public function testValidPassword(): void
    {
        $this->assertTrue($this->policy->validate('ValidP@ssw0rd123'));
        $this->assertTrue($this->policy->validate('Strong#Pwd9'));
    }

    public function testTooShortPassword(): void
    {
        $this->assertFalse($this->policy->validate('Abc@1'));
    }

    public function testMissingUppercase(): void
    {
        $this->assertFalse($this->policy->validate('validp@ssw0rd'));
    }

    public function testMissingLowercase(): void
    {
        $this->assertFalse($this->policy->validate('VALIDP@SSW0RD'));
    }

    public function testMissingNumber(): void
    {
        $this->assertFalse($this->policy->validate('ValidP@ssword'));
    }

    public function testMissingSpecialChar(): void
    {
        $this->assertFalse($this->policy->validate('ValidPassw0rd'));
    }

    public function testRequireValidThrowsException(): void
    {
        $this->expectException(SecurityException::class);
        $this->policy->requireValid('weak');
    }

    public function testRequireValidPasses(): void
    {
        $this->policy->requireValid('Strong#Pwd9!');
        $this->addToAssertionCount(1); // Ak nevyhodí výnimku, test prešiel
    }

    public function testGetMinLength(): void
    {
        $this->assertEquals(8, $this->policy->getMinLength());
    }

    public function testGetMaxLength(): void
    {
        $this->assertEquals(72, $this->policy->getMaxLength());
    }

    public function testRequiresUppercase(): void
    {
        $this->assertTrue($this->policy->requiresUppercase());
    }

    public function testRequiresLowercase(): void
    {
        $this->assertTrue($this->policy->requiresLowercase());
    }

    public function testRequiresNumbers(): void
    {
        $this->assertTrue($this->policy->requiresNumbers());
    }

    public function testRequiresSpecialChars(): void
    {
        $this->assertTrue($this->policy->requiresSpecialChars());
    }
}
