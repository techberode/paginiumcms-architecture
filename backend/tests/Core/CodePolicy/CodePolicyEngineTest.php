<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\CodePolicy;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class CodePolicyEngineTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/paginium_policy_' . uniqid();
        mkdir($this->baseDir . '/data', 0777, true);
        chdir($this->baseDir);
    }

    protected function tearDown(): void
    {
        chdir(sys_get_temp_dir());
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testBlocksForbiddenPhpFunction(): void
    {
        $engine = $this->makeEngine();
        $this->expectException(CodePolicyViolationException::class);
        $engine->validate('backend/app/Modules/Test.php', '<?php eval("bad");');
    }

    public function testAllowsValidPhp(): void
    {
        $engine = $this->makeEngine();
        $engine->validate('backend/app/Modules/Test.php', '<?php declare(strict_types=1); echo "ok";');
        $this->assertTrue(true);
    }

    private function makeEngine(): CodePolicyEngine
    {
        $validator = new FileValidator($this->baseDir);
        $settings = new SettingsRepository(
            new FileReader($validator),
            new FileWriter($validator),
            new Validator(),
            'data/settings.json'
        );

        return new CodePolicyEngine($settings, new SyntaxChecker(), new SecurityScanner());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
