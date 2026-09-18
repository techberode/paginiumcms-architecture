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

    public function testDetectsVariableFunctionCall(): void
    {
        $violations = $this->scanner->scanUntrustedIndirection(
            '<?php $fn = "exec"; $fn("ls");',
            ['exec']
        );

        $this->assertNotSame([], $violations);
        $this->assertStringContainsString('variable function', $violations[0]);
    }

    public function testDetectsVariableVariable(): void
    {
        $violations = $this->scanner->scanUntrustedIndirection(
            '<?php $a = "x"; $$a = 1;',
            ['exec']
        );

        $this->assertNotSame([], $violations);
        $this->assertStringContainsString('variable variable', $violations[0]);
    }

    public function testDetectsArrayMapForbiddenCallback(): void
    {
        $violations = $this->scanner->scanUntrustedIndirection(
            '<?php array_map("system", ["ls"]);',
            ['system']
        );

        $this->assertNotSame([], $violations);
        $this->assertStringContainsString('system', $violations[0]);
        $this->assertStringContainsString('array_map', $violations[0]);
    }

    public function testAllowsArrayMapWithClosure(): void
    {
        $violations = $this->scanner->scanUntrustedIndirection(
            '<?php array_map(static fn ($x) => $x, ["a"]);',
            ['exec', 'system']
        );

        $this->assertSame([], $violations);
    }
}
