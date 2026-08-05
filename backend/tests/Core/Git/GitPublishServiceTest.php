<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Git;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Git\Services\GitPathValidator;
use PaginiumCMS\Core\Git\Services\GitPublishService;
use PaginiumCMS\Core\Git\Services\GitPublishSettings;
use PaginiumCMS\Core\Git\Services\LocalGitProcess;
use PaginiumCMS\Core\Git\Services\LocalGitPublisher;
use PaginiumCMS\Core\Git\Services\PublishPlanner;
use PaginiumCMS\Core\Git\Services\PublishQueueStore;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class GitPublishServiceTest extends TestCase
{
    private string $baseDir;
    private string $repoDir;

    protected function setUp(): void
    {
        parent::setUp();

        if (!(new LocalGitProcess())->isAvailable()) {
            $this->markTestSkipped('git binary not available');
        }

        $this->baseDir = sys_get_temp_dir() . '/pag_git_publish_' . uniqid('', true);
        $this->repoDir = $this->baseDir . '/repo';
        mkdir($this->repoDir . '/pages', 0777, true);
        file_put_contents($this->repoDir . '/pages/demo.json', '{"title":"Demo"}');
        mkdir($this->baseDir . '/data', 0777, true);

        $init = (new LocalGitProcess())->run(
            ['init'],
            $this->repoDir
        );
        if ($init['exitCode'] !== 0) {
            $this->markTestSkipped('unable to init git repo');
        }

        (new LocalGitProcess())->run(['config', 'user.email', 'test@paginium.local'], $this->repoDir);
        (new LocalGitProcess())->run(['config', 'user.name', 'Paginium Test'], $this->repoDir);
        (new LocalGitProcess())->run(['add', 'pages/demo.json'], $this->repoDir);
        (new LocalGitProcess())->run(['commit', '-m', 'seed'], $this->repoDir);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testQueuedStrategyCreatesPendingItem(): void
    {
        $service = $this->makeService([
            'gitEnabled' => true,
            'gitPublishStrategy' => 'queued',
            'gitRepositoryPath' => $this->repoDir,
        ]);

        $result = $service->afterContentStored('pages/demo.json', '{"title":"Updated"}');
        $this->assertNotNull($result);
        $this->assertSame('pending_publish', $result->state);
        $this->assertSame(1, $service->status()['pendingCount']);
    }

    public function testQueuedReleaseCreatesSingleCommit(): void
    {
        $service = $this->makeService([
            'gitEnabled' => true,
            'gitPublishStrategy' => 'queued',
            'gitRepositoryPath' => $this->repoDir,
        ]);

        file_put_contents($this->repoDir . '/pages/demo.json', '{"title":"Updated"}');
        $service->afterContentStored('pages/demo.json', '{"title":"Updated"}');
        $service->afterContentStored('pages/second.json', '{"title":"Second"}');
        file_put_contents($this->repoDir . '/pages/second.json', '{"title":"Second"}');

        $release = $service->publishRelease('test@paginium.local');
        $this->assertTrue($release['success']);
        $this->assertSame('committed', $release['state']);
    }

    public function testImmediateStrategyCreatesCommit(): void
    {
        $service = $this->makeService([
            'gitEnabled' => true,
            'gitPublishStrategy' => 'immediate',
            'gitRepositoryPath' => $this->repoDir,
        ]);

        file_put_contents($this->repoDir . '/pages/demo.json', '{"title":"Immediate"}');
        $result = $service->afterContentStored('pages/demo.json', '{"title":"Immediate"}');
        $this->assertNotNull($result);
        $this->assertTrue($result->success);
        $this->assertSame('committed', $result->state);
    }

    public function testDisabledStrategyDoesNothing(): void
    {
        $service = $this->makeService([
            'gitEnabled' => false,
            'gitPublishStrategy' => 'disabled',
        ]);

        $this->assertNull($service->afterContentStored('pages/demo.json', '{}'));
        $this->assertSame(0, $service->status()['pendingCount']);
    }

    public function testRejectsUnsafeBranchName(): void
    {
        $validator = new GitPathValidator();
        $this->expectException(\InvalidArgumentException::class);
        $validator->assertSafeRef('main; rm -rf /', 'branch');
    }

    /**
     * @param array<string, mixed> $engineOverrides
     */
    private function makeService(array $engineOverrides): GitPublishService
    {
        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $settings = new SettingsRepository(
            $writer,
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );
        $settings->setGroup('engine', array_merge([
            'gitEnabled' => false,
            'gitPublishStrategy' => 'disabled',
            'gitPublisher' => 'local',
            'gitRepositoryPath' => '',
            'gitRemote' => 'origin',
            'gitBranch' => 'main',
            'gitPushEnabled' => false,
            'gitCommitMessageTemplate' => 'content: publish {count} change(s)',
        ], $engineOverrides));

        $gitSettings = new GitPublishSettings($settings);
        $queue = new PublishQueueStore($reader, $writer);
        $planner = new PublishPlanner($settings);
        $publisher = new LocalGitPublisher($settings, new LocalGitProcess(), new GitPathValidator());
        $logger = $this->noopLogger();

        return new GitPublishService($gitSettings, $queue, $planner, $publisher, new GitPathValidator(), $logger);
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
