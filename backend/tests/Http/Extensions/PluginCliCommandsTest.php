<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Extensions;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\UntrustedPolicyScanner;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityCatalog;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityUsageScanner;
use PaginiumCMS\Http\Extensions\Commands\PluginCreateCommand;
use PaginiumCMS\Http\Extensions\Commands\PluginScanCommand;
use PaginiumCMS\Http\Extensions\Services\ExtensionManifestValidator;
use PaginiumCMS\Http\Extensions\Services\PluginPolicyScanner;
use PaginiumCMS\Http\Extensions\Services\PluginScanService;
use PaginiumCMS\Http\Extensions\Services\PluginScaffoldService;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class PluginCliCommandsTest extends TestCase
{
    private string $baseDir;

    private string $extensionsRoot;

    private PluginScanService $scan;

    private PluginScaffoldService $scaffold;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_plugin_cli_' . uniqid('', true);
        $this->extensionsRoot = $this->baseDir . '/extensions';
        mkdir($this->extensionsRoot, 0777, true);
        mkdir($this->baseDir . '/data', 0777, true);

        $this->scan = $this->makeScanService();
        $this->scaffold = new PluginScaffoldService($this->extensionsRoot);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testScaffoldCreatesManifestAndPassesScan(): void
    {
        $root = $this->scaffold->create('seo-analyzer', 'SEO Analyzer', [PluginCapabilityCatalog::CONTENT_READ]);
        $this->assertFileExists($root . '/plugin.json');
        $this->assertFileExists($root . '/src/Hooks.php');

        /** @var array<string, mixed> $manifest */
        $manifest = JsonHelper::decode((string) file_get_contents($root . '/plugin.json'));
        $this->assertSame('seo-analyzer', $manifest['id']);
        $this->assertSame(1, $manifest['manifestVersion']);
        $this->assertContains(PluginCapabilityCatalog::CONTENT_READ, $manifest['capabilities']);

        $report = $this->scan->scan($root);
        $this->assertSame([], $report['errors']);
        $this->assertSame('seo-analyzer', $report['id']);
    }

    public function testScaffoldRefusesDuplicateId(): void
    {
        $this->scaffold->create('dup-tool', 'Dup', [PluginCapabilityCatalog::CONTENT_READ]);
        $this->expectException(RuntimeException::class);
        $this->scaffold->create('dup-tool', 'Dup', [PluginCapabilityCatalog::CONTENT_READ]);
    }

    public function testScanRejectsVariableFunction(): void
    {
        $root = $this->scaffold->create('bad-tool', 'Bad', [PluginCapabilityCatalog::CONTENT_READ]);
        file_put_contents($root . '/src/Hooks.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\BadTool;

final class Hooks
{
    public static function onBoot(array $context): void
    {
        $fn = 'exec';
        $fn('true');
    }
}
PHP);

        $report = $this->scan->scan($root);
        $this->assertNotSame([], $report['errors']);
    }

    public function testCreateCommandWritesPlugin(): void
    {
        $application = new Application();
        $application->addCommand(new PluginCreateCommand($this->scaffold, $this->scan));
        $tester = new CommandTester($application->find('plugin:create'));
        $exit = $tester->execute([
            'id' => 'hello-cli',
            '--capabilities' => 'content:read',
        ]);

        $this->assertSame(0, $exit, $tester->getDisplay());
        $this->assertDirectoryExists($this->extensionsRoot . '/hello-cli');
        $this->assertStringContainsString('Created plugin hello-cli', $tester->getDisplay());
    }

    public function testScanCommandAliasAndJsonOutput(): void
    {
        $this->scaffold->create('json-tool', 'JSON Tool', [PluginCapabilityCatalog::CONTENT_READ]);
        $application = new Application();
        $application->addCommand(new PluginScanCommand($this->scan, $this->extensionsRoot));
        $tester = new CommandTester($application->find('paginium:plugin:scan'));
        $exit = $tester->execute([
            'target' => 'json-tool',
            '--json' => true,
        ]);

        $this->assertSame(0, $exit, $tester->getDisplay());
        $decoded = json_decode($tester->getDisplay(), true);
        $this->assertIsArray($decoded);
        $this->assertTrue($decoded['ok']);
        $this->assertSame('json-tool', $decoded['id']);
    }

    public function testReferenceHelloWidgetPassesScan(): void
    {
        $root = dirname(__DIR__, 3) . '/app/Http/Extensions/hello-widget';
        $this->assertDirectoryExists($root);
        $report = $this->scan->scan($root);
        $this->assertSame([], $report['errors'], JsonHelper::encode($report['errors']));
    }

    private function makeScanService(): PluginScanService
    {
        $validator = new FileValidator($this->baseDir);
        $writer = new FileWriter($validator);
        $settings = new SettingsRepository(
            $writer,
            \PaginiumCMS\Tests\Support\StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );
        $engine = new CodePolicyEngine($settings, new SyntaxChecker(), new SecurityScanner());

        return new PluginScanService(
            new PluginPolicyScanner(new UntrustedPolicyScanner($engine)),
            new ExtensionManifestValidator(),
            new PluginCapabilityUsageScanner()
        );
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
