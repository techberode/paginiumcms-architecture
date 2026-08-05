<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Extensions;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\UntrustedPolicyScanner;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Core\Hook\HookManager;
use PaginiumCMS\Core\Hook\Services\HookEmitter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Extensions\Services\ExtensionManifestValidator;
use PaginiumCMS\Http\Extensions\Services\PluginImporter;
use PaginiumCMS\Http\Extensions\Services\PluginManager;
use PaginiumCMS\Http\Extensions\Services\PluginPolicyScanner;
use PaginiumCMS\Http\Extensions\Services\PluginRegistry;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;

final class PluginManagerTest extends TestCase
{
    private string $baseDir;
    private string $extensionsRoot;
    private string $routesRoot;
    private string $frontendRoot;
    private PluginManager $manager;
    private HookManager $hookManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_plugins_mgr_' . uniqid('', true);
        $this->extensionsRoot = $this->baseDir . '/extensions';
        $this->routesRoot = $this->baseDir . '/routes';
        $this->frontendRoot = $this->baseDir . '/frontend';
        mkdir($this->extensionsRoot, 0777, true);
        mkdir($this->routesRoot, 0777, true);
        mkdir($this->frontendRoot, 0777, true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $registry = new PluginRegistry($reader, $writer, 'data/plugins.json');
        $policy = new CodePolicyEngine(
            new SettingsRepository(
                $writer,
                \PaginiumCMS\Tests\Support\StorageTestHelper::localStorage($this->baseDir),
                new Validator(),
                'data/settings.json'
            ),
            new SyntaxChecker(),
            new SecurityScanner()
        );
        $importer = new PluginImporter(
            $registry,
            new PluginPolicyScanner(new UntrustedPolicyScanner($policy)),
            new ExtensionManifestValidator(),
            $this->extensionsRoot,
            $this->routesRoot,
            $this->frontendRoot,
            $this->baseDir
        );

        $this->hookManager = new HookManager();
        $hookEmitter = new HookEmitter($this->hookManager);
        $this->manager = new PluginManager(
            $registry,
            $importer,
            $this->hookManager,
            $hookEmitter,
            new ExtensionManifestValidator(),
            $this->extensionsRoot,
            $this->routesRoot,
            $this->frontendRoot
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testListDiscoversManifest(): void
    {
        $this->writeManifest('hello-widget', [
            'id' => 'hello-widget',
            'name' => 'Hello Widget',
            'version' => '1.0.0',
            'description' => 'Demo extension',
        ]);

        $items = $this->manager->list();
        $this->assertCount(1, $items);
        $this->assertSame('hello-widget', $items[0]['id']);
        $this->assertSame('Hello Widget', $items[0]['name']);
        $this->assertFalse($items[0]['enabled']);
        $this->assertTrue($items[0]['present']);
    }

    public function testEnableRegistersHooksOnBoot(): void
    {
        $this->writeManifest('ping-demo', [
            'id' => 'ping-demo',
            'name' => 'Ping Demo',
            'version' => '1.0.0',
            'hooks' => [
                HookCatalog::EXTENSION_BOOT => 'PaginiumCMS\\Http\\Extensions\\PingDemo\\Hooks::ping',
            ],
        ]);
        $this->writePhp('ping-demo/src/Hooks.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\PingDemo;

final class Hooks
{
    public static function ping(array $context): string
    {
        return 'pong';
    }
}
PHP);

        $this->manager->enable('ping-demo');
        $this->manager->bootEnabledExtensions();

        $this->assertTrue($this->hookManager->has(HookCatalog::EXTENSION_BOOT));
        $this->assertSame(['pong'], $this->hookManager->run(HookCatalog::EXTENSION_BOOT, [['id' => 'ping-demo']]));
    }

    public function testDisableTurnsOffEnabledFlag(): void
    {
        $this->writeManifest('hello-widget', [
            'id' => 'hello-widget',
            'name' => 'Hello Widget',
            'version' => '1.0.0',
        ]);

        $this->manager->enable('hello-widget');
        $this->manager->disable('hello-widget');

        $items = $this->manager->list();
        $this->assertFalse($items[0]['enabled']);
    }

    public function testUninstallRemovesInstalledFiles(): void
    {
        $this->writeManifest('hello-widget', [
            'id' => 'hello-widget',
            'name' => 'Hello Widget',
            'version' => '1.0.0',
        ]);

        $this->manager->enable('hello-widget');
        $this->manager->uninstall('hello-widget');

        $this->assertDirectoryDoesNotExist($this->extensionsRoot . '/hello-widget');
        $this->assertNull((new PluginRegistry(
            new FileReader(new FileValidator($this->baseDir)),
            new FileWriter(new FileValidator($this->baseDir)),
            'data/plugins.json'
        ))->get('hello-widget'));
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function writeManifest(string $id, array $manifest): void
    {
        $dir = $this->extensionsRoot . '/' . $id;
        mkdir($dir, 0777, true);
        file_put_contents(
            $dir . '/plugin.json',
            JsonHelper::encode($manifest, JSON_PRETTY_PRINT)
        );
    }

    private function writePhp(string $relativePath, string $content): void
    {
        $path = $this->extensionsRoot . '/' . $relativePath;
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, $content);
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
