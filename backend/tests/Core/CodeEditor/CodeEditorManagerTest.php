<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\CodeEditor;

use PaginiumCMS\Core\CodeEditor\Services\CodeEditorManager;
use PaginiumCMS\Core\CodeEditor\Services\CodeEditorLogger;
use PaginiumCMS\Core\CodeEditor\Services\FileBackup;
use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
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
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\TestCase;

final class CodeEditorManagerTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = sys_get_temp_dir() . '/paginium_editor_' . uniqid();
        mkdir($this->projectRoot . '/backend/app/Modules/Demo', 0777, true);
        mkdir($this->projectRoot . '/backend/config', 0777, true);
        file_put_contents($this->projectRoot . '/backend/app/Modules/Demo/sample.php', '<?php echo "ok";');
        file_put_contents($this->projectRoot . '/backend/config/app.php', '<?php return [];');
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
        $content = $manager->readFile('backend/app/Modules/Demo/sample.php');
        $this->assertStringContainsString('echo', $content);
    }

    public function testRejectsTraversalPath(): void
    {
        $manager = $this->makeManager();
        $this->assertFalse($manager->canEdit('backend/app/Modules/../Core/Foo.php'));
    }

    public function testListFilesReturnsFileInfoShape(): void
    {
        $manager = $this->makeManager();
        $files = $manager->listFiles('backend/app/Modules');
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

        $this->assertContains('backend/app/Modules/Demo/sample.php', $paths);
        $this->assertContains('backend/config/app.php', $paths);
        $this->assertSame($manager->getAllowedDirectories(), [
            'backend/app/Modules',
            'backend/app/Http/Extensions',
            'backend/resources/views/themes',
            'backend/config',
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
        $path = 'backend/app/Modules/Demo/new.php';

        $manager->createFile($path, '<?php echo "v1";');
        $this->assertStringContainsString('v1', $manager->readFile($path));

        $manager->writeFile($path, '<?php echo "v2";');
        $backups = $manager->getBackups($path);
        $this->assertNotEmpty($backups);

        $manager->restoreBackup($path, $backups[0]);
        $this->assertStringContainsString('v1', $manager->readFile($path));

        $manager->deleteFile($path);
        $this->assertFalse(file_exists($this->projectRoot . '/' . $path));
    }

    private function makeManager(): CodeEditorManager
    {
        $validator = new FileValidator($this->projectRoot);
        $settings = new SettingsRepository(
            new FileReader($validator),
            new FileWriter($validator),
            new Validator(),
            'data/settings.json'
        );

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
