<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Extensions;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Hook\HookManager;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Extensions\Services\PluginImporter;
use PaginiumCMS\Http\Extensions\Services\PluginManager;
use PaginiumCMS\Http\Extensions\Services\PluginPolicyScanner;
use PaginiumCMS\Http\Extensions\Services\PluginRegistry;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class PluginImporterTest extends TestCase
{
    private string $baseDir;
    private string $extensionsRoot;
    private string $routesRoot;
    private string $frontendRoot;
    private PluginImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_plugin_import_' . uniqid('', true);
        $this->extensionsRoot = $this->baseDir . '/backend/app/Http/Extensions';
        $this->routesRoot = $this->baseDir . '/backend/app/Http/Routes/extensions';
        $this->frontendRoot = $this->baseDir . '/frontend/src/extensions';
        mkdir($this->extensionsRoot, 0777, true);
        mkdir($this->routesRoot, 0777, true);
        mkdir($this->frontendRoot, 0777, true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $registry = new PluginRegistry($reader, $writer, 'data/plugins.json');
        $scanner = new PluginPolicyScanner($this->makePolicyEngine($reader, $writer));

        $this->importer = new PluginImporter(
            $registry,
            $scanner,
            $this->extensionsRoot,
            $this->routesRoot,
            $this->frontendRoot,
            $this->baseDir
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testImportValidZipInstallsExtension(): void
    {
        $zipPath = $this->createZip([
            'hello-widget/plugin.json' => JsonHelper::encode([
                'id' => 'hello-widget',
                'name' => 'Hello Widget',
                'version' => '1.0.0',
                'description' => 'Demo',
            ]),
            'hello-widget/src/Hooks.php' => <<<'PHP'
<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\HelloWidget;

final class Hooks
{
    public static function ping(): string
    {
        return 'pong';
    }
}
PHP,
            'hello-widget/routes.php' => <<<'PHP'
<?php

declare(strict_types=1);

use Slim\App;

return static function (App $app): void {
};
PHP,
            'hello-widget/frontend/index.ts' => 'export const hello = "world";',
        ]);

        $result = $this->importer->importZip($zipPath);

        $this->assertSame('hello-widget', $result['id']);
        $this->assertFileExists($this->extensionsRoot . '/hello-widget/plugin.json');
        $this->assertFileExists($this->routesRoot . '/hello-widget.php');
        $this->assertFileExists($this->frontendRoot . '/hello-widget/index.ts');
    }

    public function testImportRejectsForbiddenPhp(): void
    {
        $zipPath = $this->createZip([
            'bad-widget/plugin.json' => JsonHelper::encode([
                'id' => 'bad-widget',
                'name' => 'Bad Widget',
                'version' => '1.0.0',
            ]),
            'bad-widget/src/Bad.php' => '<?php eval("x");',
        ]);

        $this->expectException(CodePolicyViolationException::class);
        $this->importer->importZip($zipPath);
    }

    /**
     * @param array<string, string> $files
     */
    private function createZip(array $files): string
    {
        $zipPath = sys_get_temp_dir() . '/pag_plugin_zip_' . uniqid('', true) . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $path => $content) {
            $zip->addFromString($path, $content);
        }

        $zip->close();

        return $zipPath;
    }

    private function makePolicyEngine(FileReader $reader, FileWriter $writer): CodePolicyEngine
    {
        $settings = new SettingsRepository($reader, $writer, new Validator(), 'data/settings.json');

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
