<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Validation;

use PaginiumCMS\Core\Validation\ValidationRules;
use PHPUnit\Framework\TestCase;

/**
 * Testy zdieľaného katalógu validačných pravidiel (Iterácia 4).
 */
class ValidationRulesTest extends TestCase
{
    public function testAllContainsExpectedContexts(): void
    {
        $all = ValidationRules::all();

        $this->assertArrayHasKey('login', $all);
        $this->assertArrayHasKey('password', $all);
        $this->assertArrayHasKey('content', $all);
        $this->assertArrayHasKey('user', $all);
    }

    public function testForReturnsNullForUnknownContext(): void
    {
        $this->assertNull(ValidationRules::for('unknown'));
    }

    public function testContentRulesIncludeSlug(): void
    {
        $content = ValidationRules::for('content');
        self::assertNotNull($content);
        $this->assertContains('slug', $content['rules']['slug']);
    }

    public function testPasswordPolicyMatchesBootstrapDefaults(): void
    {
        $policy = ValidationRules::passwordPolicy();

        $this->assertSame(8, $policy['minLength']);
        $this->assertSame(72, $policy['maxLength']);
        $this->assertTrue($policy['requireUppercase']);
        $this->assertTrue($policy['requireSpecialChars']);
    }

    public function testValidatePasswordPolicyAcceptsStrongPassword(): void
    {
        $this->assertSame([], ValidationRules::validatePasswordPolicy('Abcdef1!'));
    }

    public function testValidatePasswordPolicyRejectsWeakPassword(): void
    {
        $errors = ValidationRules::validatePasswordPolicy('weak');

        $this->assertNotEmpty($errors);
    }

    public function testValidatePasswordConfirmationRequiresNonEmptyConfirm(): void
    {
        $errors = ValidationRules::validatePasswordConfirmation('Abcdef1!', '');

        $this->assertSame(['Potvrdenie hesla je povinné.'], $errors);
    }

    public function testValidatePasswordConfirmationRejectsMismatch(): void
    {
        $errors = ValidationRules::validatePasswordConfirmation('Abcdef1!', 'Abcdef1?');

        $this->assertSame(['Heslá sa nezhodujú.'], $errors);
    }

    public function testValidatePasswordConfirmationAcceptsMatch(): void
    {
        $this->assertSame([], ValidationRules::validatePasswordConfirmation('Abcdef1!', 'Abcdef1!'));
    }
}
