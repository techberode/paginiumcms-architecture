<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\CodeEditor;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PHPUnit\Framework\TestCase;

final class SyntaxCheckerTest extends TestCase
{
    private SyntaxChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new SyntaxChecker();
    }

    public function testValidPhpPasses(): void
    {
        $this->assertTrue($this->checker->check(
            'backend/app/Modules/Demo.php',
            '<?php declare(strict_types=1); echo "ok";'
        ));
    }

    public function testInvalidPhpFails(): void
    {
        $this->assertFalse($this->checker->check(
            'backend/app/Modules/Demo.php',
            '<?php echo "unclosed'
        ));
        $this->assertNotEmpty($this->checker->getLastError());
    }

    public function testValidJsonPasses(): void
    {
        $this->assertTrue($this->checker->check('config.json', '{"ok": true}'));
    }

    public function testInvalidJsonFails(): void
    {
        $this->assertFalse($this->checker->check('config.json', '{invalid'));
        $this->assertNotEmpty($this->checker->getLastError());
    }
}
