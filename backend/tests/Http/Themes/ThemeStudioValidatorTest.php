<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Themes;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\UntrustedMarkupScanner;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Http\Themes\Services\ThemeManifestValidator;
use PaginiumCMS\Http\Themes\Services\ThemeStudioService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioValidator;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class ThemeStudioValidatorTest extends TestCase
{
    private string $baseDir;
    private ThemeStudioValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_theme_validate_' . uniqid('', true);
        mkdir($this->baseDir . '/themes', 0777, true);

        $settings = new SettingsRepository(
            new FileWriter(new FileValidator($this->baseDir)),
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );

        $this->validator = new ThemeStudioValidator(
            new ThemeStudioService($this->baseDir . '/themes'),
            new CodePolicyEngine($settings, new SyntaxChecker(), new SecurityScanner(), new UntrustedMarkupScanner()),
            new ThemeManifestValidator(),
            new UntrustedMarkupScanner(),
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testHappyHtmlIsValid(): void
    {
        $result = $this->validator->validate(
            'clean-journal',
            'templates/default.html',
            "<body>\n  {{> header}}\n  <main>{{content}}</main>\n</body>\n"
        );

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['markers']);
    }

    public function testScriptTagReturnsMarkerOnTheOffendingLine(): void
    {
        $result = $this->validator->validate(
            'clean-journal',
            'templates/default.html',
            "<main>{{content}}</main>\n<script>alert(1)</script>\n"
        );

        $this->assertFalse($result['valid']);
        $this->assertSame(2, $result['markers'][0]['line']);
        $this->assertStringContainsString('<script>', $result['markers'][0]['message']);
    }

    public function testEventHandlerIsRejected(): void
    {
        $result = $this->validator->validate(
            'x',
            'templates/default.html',
            '<img src="/x.png" onerror="alert(1)">'
        );

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['markers']);
    }

    public function testJavascriptUrlIsRejected(): void
    {
        $result = $this->validator->validate(
            'x',
            'templates/default.html',
            '<a href="javascript:alert(1)">x</a>'
        );

        $this->assertFalse($result['valid']);
    }

    public function testCssJavascriptUrlIsRejected(): void
    {
        $result = $this->validator->validate(
            'x',
            'assets/theme.css',
            "body {\n  background: url(javascript:alert(1));\n}\n"
        );

        $this->assertFalse($result['valid']);
        $this->assertSame(2, $result['markers'][0]['line']);
    }

    public function testDoesNotWriteFiles(): void
    {
        $this->validator->validate('x', 'templates/default.html', '<script>x</script>');
        $this->assertFalse(is_dir($this->baseDir . '/themes/x'));
    }

    public function testPathTraversalIsRejected(): void
    {
        $this->expectException(ThemeStudioException::class);
        $this->validator->validate('x', '../theme.json', '{}');
    }

    public function testInvalidManifestIsRejected(): void
    {
        $result = $this->validator->validate('untitled-theme', 'theme.json', '{"id":"untitled-theme"}');
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('name', $result['markers'][0]['message']);
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
