<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Themes;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\UntrustedMarkupScanner;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Http\Themes\Services\ThemeManifestValidator;
use PaginiumCMS\Http\Themes\Services\ThemeRegistry;
use PaginiumCMS\Http\Themes\Services\ThemeScriptIntegrityService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioPersistService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioValidator;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class ThemeStudioPersistServiceTest extends TestCase
{
    private string $baseDir;
    private ThemeStudioPersistService $persist;
    private ThemeStudioService $studio;
    private ThemeRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_theme_persist_' . uniqid('', true);
        mkdir($this->baseDir . '/themes', 0777, true);
        mkdir($this->baseDir . '/frontend', 0777, true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $this->registry = new ThemeRegistry(
            new FileReader($validator),
            new FileWriter($validator),
            'data/themes.json'
        );

        $settings = new SettingsRepository(
            new FileWriter($validator),
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );

        $this->studio = new ThemeStudioService($this->baseDir . '/themes');
        $studioValidator = new ThemeStudioValidator(
            $this->studio,
            new CodePolicyEngine($settings, new SyntaxChecker(), new SecurityScanner(), new UntrustedMarkupScanner()),
            new ThemeManifestValidator(),
            new UntrustedMarkupScanner(),
        );

        $this->persist = new ThemeStudioPersistService(
            $this->studio,
            $studioValidator,
            new ThemeManifestValidator(),
            new ThemeScriptIntegrityService($this->baseDir . '/themes'),
            $this->registry,
            $this->baseDir . '/frontend',
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testSaveWritesPackageAndRegistry(): void
    {
        $result = $this->persist->save('studio-tmp', $this->safeFiles('studio-tmp'));

        $this->assertFalse($result['blocked']);
        $this->assertSame('studio-tmp', $result['themeId']);
        $this->assertContains('theme.json', $result['written']);
        $this->assertFileExists($this->baseDir . '/themes/studio-tmp/templates/default.html');
        $this->assertNotNull($this->registry->get('studio-tmp'));
        $this->assertFalse($this->registry->get('studio-tmp')->enabled);
    }

    public function testHostileHtmlDoesNotWrite(): void
    {
        $files = $this->safeFiles('studio-tmp');
        $files['templates/default.html'] = '<script>alert(1)</script>';

        $result = $this->persist->save('studio-tmp', $files);

        $this->assertTrue($result['blocked']);
        $this->assertSame([], $result['written']);
        $this->assertDirectoryDoesNotExist($this->baseDir . '/themes/studio-tmp');
        $this->assertNull($this->registry->get('studio-tmp'));
    }

    public function testUndeclaredJavascriptIsRejected(): void
    {
        $files = $this->safeFiles('studio-tmp');
        $files['assets/theme.js'] = "const themeName = 'studio';\n";

        $this->expectException(ThemeStudioException::class);
        $this->persist->save('studio-tmp', $files);
    }

    public function testCoreThemeIdIsRejected(): void
    {
        $this->expectException(ThemeStudioException::class);
        $this->persist->save('paginium-core', $this->safeFiles('paginium-core'));
    }

    /**
     * @return array<string, string>
     */
    private function safeFiles(string $id): array
    {
        $manifest = <<<JSON
{
  "manifestVersion": 1,
  "id": "{$id}",
  "name": "Studio temp",
  "version": "0.1.0",
  "slots": ["header", "main", "footer"],
  "templates": ["default"]
}
JSON;

        return [
            'theme.json' => $manifest,
            'templates/default.html' => "<body>\n  {{> header}}\n  <main>{{content}}</main>\n  {{> footer}}\n</body>\n",
            'partials/header.html' => '<header><a href="/">{{siteName}}</a></header>',
            'partials/footer.html' => '<footer><p>{{siteName}}</p></footer>',
            'assets/theme.css' => "body { margin: 0; }\n",
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
