<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Themes;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\UntrustedPolicyScanner;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Themes\Services\ThemeImporter;
use PaginiumCMS\Http\Themes\Services\ThemeManifestValidator;
use PaginiumCMS\Http\Themes\Services\ThemeRegistry;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ThemeImporterTest extends TestCase
{
    private string $baseDir;
    private string $themesRoot;
    private string $frontendRoot;
    private ThemeImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_theme_import_' . uniqid('', true);
        $this->themesRoot = $this->baseDir . '/backend/resources/views/themes';
        $this->frontendRoot = $this->baseDir . '/frontend/src/themes';
        mkdir($this->themesRoot, 0777, true);
        mkdir($this->frontendRoot, 0777, true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $registry = new ThemeRegistry($reader, $writer, 'data/themes.json');

        $settings = new SettingsRepository(
            $writer,
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );

        $scanner = new UntrustedPolicyScanner(
            new CodePolicyEngine($settings, new SyntaxChecker(), new SecurityScanner())
        );

        $this->importer = new ThemeImporter(
            $registry,
            $scanner,
            new ThemeManifestValidator(),
            $this->themesRoot,
            $this->frontendRoot,
            $this->baseDir
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testImportValidZipInstallsTheme(): void
    {
        $zipPath = $this->createZip([
            'clean-journal/theme.json' => JsonHelper::encode([
                'manifestVersion' => 1,
                'id' => 'clean-journal',
                'name' => 'Clean Journal',
                'version' => '1.0.0',
                'slots' => ['header', 'main', 'footer'],
                'supports' => ['appearance-tokens'],
            ]),
            'clean-journal/templates/default.html' => '<main class="pg-main">{{content}}</main>',
        ]);

        $result = $this->importer->importZip($zipPath);

        $this->assertSame('clean-journal', $result['id']);
        $this->assertFileExists($this->themesRoot . '/clean-journal/theme.json');
    }

    public function testImportRejectsEvalPhp(): void
    {
        $zipPath = $this->createZip([
            'evil-theme/theme.json' => JsonHelper::encode([
                'id' => 'evil-theme',
                'name' => 'Evil',
                'version' => '1.0.0',
            ]),
            'evil-theme/partials/header.php' => '<?php eval("bad");',
        ]);

        $this->expectException(CodePolicyViolationException::class);
        $this->importer->importZip($zipPath);
    }

    public function testImportRejectsZipSlip(): void
    {
        $zipPath = sys_get_temp_dir() . '/pag_theme_slip_' . uniqid('', true) . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('../escape/theme.json', '{}');
        $zip->close();

        $this->expectException(\RuntimeException::class);
        $this->importer->importZip($zipPath);
    }

    /**
     * @param array<string, string> $files
     */
    private function createZip(array $files): string
    {
        $zipPath = sys_get_temp_dir() . '/pag_theme_zip_' . uniqid('', true) . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $path => $content) {
            $zip->addFromString($path, $content);
        }

        $zip->close();

        return $zipPath;
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
