<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Validation;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\VisitorPhoneGuard;
use PHPUnit\Framework\TestCase;

final class VisitorPhoneGuardTest extends TestCase
{
    private VisitorPhoneGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new VisitorPhoneGuard();
    }

    public function testNormalizesPrefixAndNationalNumber(): void
    {
        $this->assertSame('+421909554887', $this->guard->normalize('+421', '909554887'));
        $this->assertSame('+421909554887', $this->guard->normalize('+421', '909 554 887'));
        $this->assertSame('+421909554887', $this->guard->normalizeCombined('+421 909-554-887'));
    }

    public function testRejectsMissingPrefixOrLocalFormat(): void
    {
        foreach (['0909554887', '421909554887', '+421', '+012345678'] as $invalid) {
            try {
                $this->guard->normalizeCombined($invalid);
                $this->fail('Expected ValidationException for ' . $invalid);
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('phone', $exception->getErrors());
            }
        }

        try {
            $this->guard->normalize('421', '909554887');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('phone', $exception->getErrors());
        }
    }
}
