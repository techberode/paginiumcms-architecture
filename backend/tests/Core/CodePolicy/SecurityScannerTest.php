<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\CodePolicy;

use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PHPUnit\Framework\TestCase;

final class SecurityScannerTest extends TestCase
{
    private SecurityScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new SecurityScanner();
    }

    public function testDetectsEvalKeyword(): void
    {
        $violations = $this->scanner->scanPhp('<?php eval("x");', ['eval']);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString('eval', $violations[0]);
    }

    public function testDetectsForbiddenFunctionCall(): void
    {
        $violations = $this->scanner->scanPhp('<?php shell_exec("ls");', ['shell_exec']);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString('shell_exec', $violations[0]);
    }

    public function testIgnoresStringLiteralContainingForbiddenName(): void
    {
        $violations = $this->scanner->scanPhp('<?php $x = "shell_exec";', ['shell_exec']);

        $this->assertSame([], $violations);
    }

    public function testAllowsSafePhp(): void
    {
        $violations = $this->scanner->scanPhp(
            '<?php declare(strict_types=1); echo "ok";',
            ['eval', 'exec', 'shell_exec']
        );

        $this->assertSame([], $violations);
    }
}
