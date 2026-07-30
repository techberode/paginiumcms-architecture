<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\CodePolicy;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\ShortcodeDefinitionPolicy;
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
        $this->addToAssertionCount(1);
    }

    public function testUntrustedStillScannedWhenPolicyDisabled(): void
    {
        $engine = $this->makeEngine(['enabled' => false]);
        $this->expectException(CodePolicyViolationException::class);
        $engine->validate(
            'backend/app/Http/Extensions/evil/Evil.php',
            '<?php declare(strict_types=1); namespace PaginiumCMS\\Http\\Extensions\\evil; eval("x");'
        );
    }

    public function testValidateUntrustedRequiresStrictTypes(): void
    {
        $engine = $this->makeEngine();
        $this->expectException(CodePolicyViolationException::class);
        $engine->validateUntrusted(
            'custom/snippet.php',
            '<?php namespace PaginiumCMS\\Http\\Extensions\\x; echo "ok";'
        );
    }

    public function testIsUntrustedPathDetectsLayoutAndThemes(): void
    {
        $engine = $this->makeEngine();
        self::assertTrue($engine->isUntrustedPath('data/shortcodes/foo.json'));
        self::assertTrue($engine->isUntrustedPath('themes/acme/layout.php'));
        self::assertFalse($engine->isUntrustedPath('backend/app/Modules/Gallery/Services/GalleryRepository.php'));
    }

    public function testShortcodeDefinitionPolicyAcceptsSafeExpand(): void
    {
        $policy = new ShortcodeDefinitionPolicy();
        $policy->validate([
            'name' => 'section',
            'version' => 1,
            'attrs' => [
                'layout' => ['type' => 'enum', 'options' => ['2-columns', 'stack']],
            ],
            'expand' => '<div class="pg-grid pg-grid-cols-1">{{body}}</div>',
        ]);
        $this->addToAssertionCount(1);
    }

    public function testShortcodeDefinitionPolicyRejectsScript(): void
    {
        $policy = new ShortcodeDefinitionPolicy();
        $this->expectException(CodePolicyViolationException::class);
        $policy->validate([
            'name' => 'evil',
            'expand' => '<script>alert(1)</script>',
        ]);
    }

    public function testShortcodeDefinitionPolicyRejectsNonPgClass(): void
    {
        $policy = new ShortcodeDefinitionPolicy();
        $this->expectException(CodePolicyViolationException::class);
        $policy->validate([
            'name' => 'card',
            'expand' => '<div class="grid grid-cols-3">x</div>',
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeEngine(array $overrides = []): CodePolicyEngine
    {
        $validator = new FileValidator($this->baseDir);
        $settings = new SettingsRepository(
            new FileReader($validator),
            new FileWriter($validator),
            new Validator(),
            'data/settings.json'
        );

        if ($overrides !== []) {
            $settings->setGroup('codePolicy', array_merge([
                'enabled' => true,
                'strictMode' => false,
                'maxFileSizeKb' => 512,
                'forbiddenPhpFunctions' => 'eval,exec,shell_exec,system,passthru,proc_open,popen,assert,create_function',
            ], $overrides));
        }

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
