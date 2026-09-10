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
use PaginiumCMS\Http\Themes\Services\ThemeScriptIntegrityService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioNormalizeService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioValidator;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class ThemeStudioNormalizeServiceTest extends TestCase
{
    private string $baseDir;
    private ThemeStudioNormalizeService $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_theme_normalize_' . uniqid('', true);
        mkdir($this->baseDir . '/themes', 0777, true);

        $settings = new SettingsRepository(
            new FileWriter(new FileValidator($this->baseDir)),
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );

        $studio = new ThemeStudioService($this->baseDir . '/themes');
        $validator = new ThemeStudioValidator(
            $studio,
            new CodePolicyEngine($settings, new SyntaxChecker(), new SecurityScanner(), new UntrustedMarkupScanner()),
            new ThemeManifestValidator(),
            new UntrustedMarkupScanner(),
        );

        $this->normalizer = new ThemeStudioNormalizeService(
            $studio,
            $validator,
            new ThemeScriptIntegrityService($this->baseDir . '/themes'),
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testMapsSemanticSlotsAndDropsScripts(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
  <title>Pastel Shop</title>
  <script src="https://cdn.example/app.js"></script>
  <style>body { color: navy; }</style>
</head>
<body>
  <header class="top"><a href="/">Shop</a></header>
  <main><h1 onclick="alert(1)">Hello</h1><script>alert(1)</script></main>
  <footer><p>Bye</p></footer>
</body>
</html>
HTML;

        $result = $this->normalizer->normalize('untitled-theme', [], $html);

        $this->assertFalse($result['rejected']);
        $this->assertArrayHasKey('templates/default.html', $result['files']);
        $this->assertArrayHasKey('partials/header.html', $result['files']);
        $this->assertArrayHasKey('partials/footer.html', $result['files']);
        $this->assertArrayHasKey('assets/theme.css', $result['files']);
        $this->assertStringContainsString('{{> header}}', $result['files']['templates/default.html']);
        $this->assertStringContainsString('{{content}}', $result['files']['templates/default.html']);
        $this->assertStringContainsString('<header', $result['files']['partials/header.html']);
        $this->assertStringContainsString('Shop', $result['files']['partials/header.html']);
        $this->assertStringContainsString('navy', $result['files']['assets/theme.css']);
        $this->assertStringNotContainsString('<script', strtolower($result['files']['templates/default.html']));
        $this->assertStringNotContainsString('<script', strtolower($result['files']['partials/header.html']));
        $this->assertStringNotContainsString('onclick', strtolower($result['files']['partials/header.html'] . $result['files']['partials/footer.html']));
        $this->assertNotEmpty($result['dropped']);
        $this->assertFalse(is_dir($this->baseDir . '/themes/untitled-theme'));
    }

    public function testPhpRejectsEntireImport(): void
    {
        $result = $this->normalizer->normalize('untitled-theme', [], '<html><body><?php echo 1; ?></body></html>');

        $this->assertTrue($result['rejected']);
        $this->assertSame([], $result['files']);
        $this->assertNotEmpty($result['dropped']);
        $this->assertFalse(is_dir($this->baseDir . '/themes/untitled-theme'));
    }

    public function testBladeRejectsEntireImport(): void
    {
        $result = $this->normalizer->normalize('untitled-theme', [], '<html><body>@extends("layout")</body></html>');

        $this->assertTrue($result['rejected']);
        $this->assertSame([], $result['files']);
    }

    public function testCssJavascriptUrlIsStripped(): void
    {
        $result = $this->normalizer->normalize('untitled-theme', [
            'index.html' => '<html><body><header>H</header><main>M</main><footer>F</footer></body></html>',
            'theme.css' => 'body{background:url(javascript:alert(1));}@import url(https://evil/x.css);',
        ]);

        $this->assertFalse($result['rejected']);
        $this->assertStringNotContainsString('javascript:', $result['files']['assets/theme.css']);
        $this->assertStringNotContainsString('https://evil', $result['files']['assets/theme.css']);
        $this->assertNotEmpty($result['dropped']);
    }

    public function testHostileJavascriptIsDroppedAndNotPackaged(): void
    {
        $result = $this->normalizer->normalize('untitled-theme', [], '<html><body><header>H</header><main>M</main><footer>F</footer></body></html>', '', "eval('x');\n");

        $this->assertFalse($result['rejected']);
        $this->assertArrayNotHasKey('assets/theme.js', $result['files']);
        $this->assertStringContainsString('Dropped JavaScript', implode(' ', $result['dropped']));
        $this->assertStringNotContainsString('eval', $result['files']['theme.json']);
    }

    public function testCleanJavascriptIsKeptWithIntegrity(): void
    {
        $result = $this->normalizer->normalize(
            'untitled-theme',
            [],
            '<html><body><header>H</header><main>M</main><footer>F</footer></body></html>',
            '',
            "const themeName = 'untitled';\n",
        );

        $this->assertFalse($result['rejected']);
        $this->assertArrayHasKey('assets/theme.js', $result['files']);
        $this->assertStringContainsString('themeName', $result['files']['assets/theme.js']);
        $this->assertStringContainsString('sha384-', $result['files']['theme.json']);
        $this->assertStringContainsString('assets/theme.js', $result['files']['theme.json']);
    }

    public function testEmptyPayloadIsRejected(): void
    {
        $this->expectException(ThemeStudioException::class);
        $this->normalizer->normalize('untitled-theme', []);
    }

    public function testAsideMapsToSidebarPartial(): void
    {
        $html = '<html><body><header>H</header><aside class="side">Nav</aside><main>M</main><footer>F</footer></body></html>';
        $result = $this->normalizer->normalize('untitled-theme', [], $html);

        $this->assertFalse($result['rejected']);
        $this->assertArrayHasKey('partials/sidebar.html', $result['files']);
        $this->assertStringContainsString('{{> sidebar}}', $result['files']['templates/default.html']);
        $this->assertStringContainsString('sidebar', $result['files']['theme.json']);
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
