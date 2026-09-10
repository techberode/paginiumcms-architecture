<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Themes;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\UntrustedMarkupScanner;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Http\Themes\Services\ThemeManifestValidator;
use PaginiumCMS\Http\Themes\Services\ThemeStudioPreviewService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioValidator;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class ThemeStudioPreviewServiceTest extends TestCase
{
    private string $baseDir;
    private ThemeStudioPreviewService $preview;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_theme_preview_' . uniqid('', true);
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

        $this->preview = new ThemeStudioPreviewService(
            $studio,
            $validator,
            new ContentSecuritySanitizer($settings),
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testHappySlotHtmlAndCssBuildSandboxedDocument(): void
    {
        $result = $this->preview->preview('untitled-theme', $this->safeFiles());

        $this->assertFalse($result['blocked']);
        $this->assertSame('templates/default.html', $result['template']);
        $this->assertSame([], $result['issues']);
        $this->assertStringContainsString(ThemeStudioPreviewService::DOCUMENT_CSP, $result['document']);
        $this->assertStringContainsString('script-src \'none\'', $result['document']);
        $this->assertStringContainsString('pg-brand', $result['document']);
        $this->assertStringContainsString('Sample content', $result['document']);
        $this->assertStringContainsString('.pg-main', $result['document']);
        $this->assertStringNotContainsString('<script', strtolower($result['document']));
        $this->assertStringNotContainsString('alert(1)', $result['document']);
        $this->assertStringNotContainsString('console.log', $result['document']);
        $this->assertFalse(is_dir($this->baseDir . '/themes/untitled-theme'));
    }

    public function testHostileHtmlBlocksPreviewAndLeavesDocumentEmpty(): void
    {
        $files = $this->safeFiles();
        $files['templates/default.html'] = "<img src=x onerror=alert(1)>\n<script>alert(1)</script>\n";

        $result = $this->preview->preview('untitled-theme', $files);

        $this->assertTrue($result['blocked']);
        $this->assertSame('', $result['document']);
        $this->assertNotEmpty($result['issues']);
        $this->assertSame('templates/default.html', $result['issues'][0]['relativePath']);
        $this->assertFalse(is_dir($this->baseDir . '/themes/untitled-theme'));
    }

    public function testValidJavascriptIsNotInjectedIntoDocument(): void
    {
        $files = $this->safeFiles();
        $files['assets/theme.js'] = "const themeName = 'untitled';\n";

        $result = $this->preview->preview('untitled-theme', $files);

        $this->assertFalse($result['blocked']);
        $this->assertStringNotContainsString('themeName', $result['document']);
        $this->assertStringNotContainsString('<script', strtolower($result['document']));
    }

    public function testHostileJavascriptBlocksPreview(): void
    {
        $files = $this->safeFiles();
        $files['assets/theme.js'] = "eval('alert(1)');\n";

        $result = $this->preview->preview('untitled-theme', $files);

        $this->assertTrue($result['blocked']);
        $this->assertSame('', $result['document']);
    }

    public function testInvalidManifestDoesNotBlockPreview(): void
    {
        $files = $this->safeFiles();
        $files['theme.json'] = '{"id":"untitled-theme"}';

        $result = $this->preview->preview('untitled-theme', $files);

        $this->assertFalse($result['blocked']);
        $this->assertStringContainsString('Sample content', $result['document']);
    }

    public function testPartialPathTraversalTokensAreNotExpandedFromDisk(): void
    {
        $files = $this->safeFiles();
        $files['templates/default.html'] = '{{> ../secret}}<main>{{content}}</main>';

        $result = $this->preview->preview('untitled-theme', $files);

        $this->assertFalse($result['blocked']);
        $this->assertStringNotContainsString('secret', $result['document']);
    }

    public function testMissingFilesPayloadIsRejected(): void
    {
        $this->expectException(ThemeStudioException::class);
        $this->preview->preview('untitled-theme', []);
    }

    /**
     * @return array<string, string>
     */
    private function safeFiles(): array
    {
        return [
            'templates/default.html' => "<body>\n  {{> header}}\n  <main class=\"pg-main\">{{content}}</main>\n  {{> footer}}\n</body>\n",
            'partials/header.html' => '<header class="pg-header"><a class="pg-brand" href="/">{{siteName}}</a></header>',
            'partials/footer.html' => '<footer class="pg-footer"><p>{{siteName}}</p></footer>',
            'assets/theme.css' => "body { margin: 0; }\n.pg-main { max-width: 40rem; }\n",
            'theme.json' => <<<'JSON'
{
  "manifestVersion": 1,
  "id": "untitled-theme",
  "name": "Untitled theme",
  "version": "0.1.0",
  "minCmsVersion": "2.1.0",
  "slots": ["header", "main", "footer"],
  "templates": ["default"]
}
JSON,
        ];
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
