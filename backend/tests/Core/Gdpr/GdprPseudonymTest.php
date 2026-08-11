<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Gdpr;

use PaginiumCMS\Core\Gdpr\GdprPseudonym;
use PHPUnit\Framework\TestCase;

final class GdprPseudonymTest extends TestCase
{
    public function testPseudonymIsStableForSubject(): void
    {
        $first = GdprPseudonym::forSubject('user_abc123');
        $second = GdprPseudonym::forSubject('user_abc123');

        $this->assertSame($first, $second);
        $this->assertStringStartsWith('anon_', $first);
    }

    public function testEmailUsesInvalidDomain(): void
    {
        $email = GdprPseudonym::emailForSubject('user_xyz');

        $this->assertTrue(GdprPseudonym::isAnonymizedEmail($email));
        $this->assertStringEndsWith('@anonymized.invalid', $email);
    }
}
