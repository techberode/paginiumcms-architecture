<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\CodeEditor;

use PaginiumCMS\Core\CodeEditor\Services\CodeEditorManager;
use PaginiumCMS\Core\CodeEditor\Services\CodeEditorLogger;
use PaginiumCMS\Core\CodeEditor\Services\FileBackup;
use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\ShortcodeDefinitionPolicy;
use PaginiumCMS\Core\Config\ConfigManager;
use PaginiumCMS\Core\Developer\DeveloperMode;
use PaginiumCMS\Core\Developer\DeveloperModeGate;
use PaginiumCMS\Core\Developer\DevTokenGenerator;
use PaginiumCMS\Core\Developer\DevTokenRegistry;
use PaginiumCMS\Core\Developer\Services\DeveloperLogger;
use PaginiumCMS\Core\Event\EventDispatcher;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Tests\Support\SettingsTestHelper;
use PHPUnit\Framework\TestCase;

final class CodeEditorManagerTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = sys_get_temp_dir() . '/paginium_editor_' . uniqid();
        mkdir($this->projectRoot . '/backend/app/Http/Extensions/Demo', 0777, true);
        mkdir($this->projectRoot . '/data/shortcodes/definitions', 0777, true);
        mkdir($this->projectRoot . '/data/layout', 0777, true);
        file_put_contents(
            $this->projectRoot . '/backend/app/Http/Extensions/Demo/sample.php',
            "<?php\n\ndeclare(strict_types=1);\n\necho \"ok\";\n"
        );
        chdir($this->projectRoot);
    }

    protected function tearDown(): void
    {
        chdir(sys_get_temp_dir());
        $this->removeDir($this->projectRoot);
        parent::tearDown();
    }

    public function testReadsFileWithProjectRootPath(): void
    {
        $manager = $this->makeManager();
        $content = $manager->readFile('backend/app/Http/Extensions/Demo/sample.php');
        $this->assertStringContainsString('echo', $content);
    }

    public function testRejectsTraversalPath(): void
    {
        $manager = $this->makeManager();
        $this->assertFalse($manager->canEdit('backend/app/Http/Extensions/../Core/Foo.php'));
    }

    public function testListFilesReturnsFileInfoShape(): void
    {
        $manager = $this->makeManager();
        $files = $manager->listFiles('backend/app/Http/Extensions');
        $this->assertNotEmpty($files);
        $this->assertArrayHasKey('path', $files[0]);
        $this->assertArrayHasKey('name', $files[0]);
        $this->assertArrayHasKey('extension', $files[0]);
    }

    public function testListAllAllowedFilesMergesAllowedRootsOnly(): void
    {
        $manager = $this->makeManager();

        $files = $manager->listAllAllowedFiles();
        $paths = array_column($files, 'path');

        $this->assertContains('backend/app/Http/Extensions/Demo/sample.php', $paths);
        $this->assertSame($manager->getAllowedDirectories(), [
            'backend/app/Http/Extensions',
            'backend/resources/views/themes',
            'data/shortcodes/definitions',
            'data/layout',
        ]);
    }

    public function testListFilesRejectsForbiddenDirectory(): void
    {
        $manager = $this->makeManager();

        $this->expectException(\RuntimeException::class);
        $manager->listFiles('backend/app/Core');
    }

    public function testCreateDeleteAndRestoreFile(): void
    {
        $manager = $this->makeManager();
        $path = 'backend/app/Http/Extensions/Demo/new.php';

        $manager->createFile($path, $this->untrustedExtensionPhp('v1'));
        $this->assertStringContainsString('v1', $manager->readFile($path));

        $manager->writeFile($path, $this->untrustedExtensionPhp('v2'));
        $backups = $manager->getBackups($path);
        $this->assertNotEmpty($backups);

        $manager->restoreBackup($path, $backups[0]);
        $this->assertStringContainsString('v1', $manager->readFile($path));

        $manager->deleteFile($path);
        $this->assertFalse(file_exists($this->projectRoot . '/' . $path));
    }

    private function untrustedExtensionPhp(string $marker): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\necho \"{$marker}\";\n";
    }

    private function makeManager(): CodeEditorManager
    {
        $validator = new FileValidator($this->projectRoot);
        $settings = SettingsTestHelper::repository($this->projectRoot);

        $policy = new CodePolicyEngine($settings, new SyntaxChecker(), new SecurityScanner());
        $userRepo = new UserRepository(new FileReader($validator), new FileWriter($validator), 'users');
        $logger = new CodeEditorLogger(
            $this->noopLogger(),
            new DeveloperMode(
                new ConfigManager(),
                new EventDispatcher(),
                new DeveloperModeGate(new DevTokenGenerator('test-secret'), new DevTokenRegistry(), $userRepo),
                new DeveloperLogger()
            )
        );

        return new CodeEditorManager(
            new SyntaxChecker(),
            new FileBackup($this->projectRoot),
            $logger,
            $policy,
            new ShortcodeDefinitionPolicy(),
            $this->projectRoot
        );
    }

    private function noopLogger(): LoggerInterface
    {
        return new class implements LoggerInterface {
            public function info(string $message, array $context = []): void
            {
            }

            public function warning(string $message, array $context = []): void
            {
            }

            public function error(string $message, array $context = []): void
            {
            }

            public function critical(string $message, array $context = []): void
            {
            }

            public function debug(string $message, array $context = []): void
            {
            }

            public function log(string $severity, string $message, array $context = []): void
            {
            }

            public function writeEntry(\PaginiumCMS\Core\Logging\Models\LogEntry $entry): void
            {
            }

            public function getLastEntries(int $limit = 100): array
            {
                return [];
            }

            public function getEntriesBySeverity(string $severity, int $limit = 100): array
            {
                return [];
            }

            public function getEntriesByCategory(string $category, int $limit = 100): array
            {
                return [];
            }

            public function clearOldEntries(int $days = 30): int
            {
                return 0;
            }
        };
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
