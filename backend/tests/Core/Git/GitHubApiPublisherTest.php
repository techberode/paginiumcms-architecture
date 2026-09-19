<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Git;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Git\Contracts\GitHubApiTransport;
use PaginiumCMS\Core\Git\Services\GitHubApiPublisher;
use PaginiumCMS\Core\Git\Services\GitPathValidator;
use PaginiumCMS\Core\Git\Services\GitPublishService;
use PaginiumCMS\Core\Git\Services\GitPublishSettings;
use PaginiumCMS\Core\Git\Services\LocalGitProcess;
use PaginiumCMS\Core\Git\Services\LocalGitPublisher;
use PaginiumCMS\Core\Git\Services\PublishPlanner;
use PaginiumCMS\Core\Git\Services\PublishQueueStore;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Tests\Support\GitPublishTestHelper;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class GitHubApiPublisherTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_gh_api_pub_' . uniqid('', true);
        mkdir($this->baseDir . '/pages', 0777, true);
        mkdir($this->baseDir . '/data', 0777, true);
        file_put_contents($this->baseDir . '/pages/home.json', '{"title":"Home"}');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testQueuedReleaseCreatesRemoteCommitAndPush(): void
    {
        $transport = new RecordingGitHubTransport();
        $service = $this->makeService($transport, [
            'gitEnabled' => true,
            'gitPublishStrategy' => 'queued',
            'gitPublisher' => 'github_api',
            'gitGithubRepository' => 'acme/site',
            'gitGithubToken' => 'ghp_test_token',
            'gitBranch' => 'main',
        ]);

        $queued = $service->afterContentStored('pages/home.json', '{"title":"Home"}');
        $this->assertNotNull($queued);
        $this->assertSame('pending_publish', $queued->state);

        $release = $service->publishRelease('ops@paginium.local');
        $this->assertTrue($release['success']);
        $this->assertSame('pushed', $release['state']);
        $this->assertSame('abc123commit', $release['commitHash']);

        $methods = array_map(static fn (array $call): string => $call['method'], $transport->calls);
        $this->assertContains('POST', $methods);
        $this->assertContains('PATCH', $methods);
        $this->assertSame(0, $service->status()['pendingCount']);
    }

    public function testRejectsPathOutsideAllowList(): void
    {
        $publisher = $this->makePublisher(new RecordingGitHubTransport());
        $this->expectException(\InvalidArgumentException::class);
        $publisher->stage(['../secrets.env']);
    }

    public function testStatusWithoutTokenIsNotConfigured(): void
    {
        $publisher = $this->makePublisher(new RecordingGitHubTransport(), [
            'gitGithubRepository' => 'acme/site',
            'gitGithubToken' => '',
        ]);
        $status = $publisher->status();
        $this->assertFalse($status['repositoryConfigured']);
        $this->assertSame('github_api', $status['publisher']);
    }

    /**
     * @param array<string, mixed> $engineOverrides
     */
    private function makeService(GitHubApiTransport $transport, array $engineOverrides): GitPublishService
    {
        [$settings, $reader] = $this->bootSettings($engineOverrides);
        $gitSettings = new GitPublishSettings($settings);
        $writer = new FileWriter(new FileValidator($this->baseDir));

        return new GitPublishService(
            $gitSettings,
            new PublishQueueStore($reader, $writer),
            new PublishPlanner($settings),
            new LocalGitPublisher($settings, new LocalGitProcess(), new GitPathValidator()),
            new GitHubApiPublisher($gitSettings, new GitPathValidator(), $reader, $transport),
            new GitPathValidator(),
            GitPublishTestHelper::noopLogger()
        );
    }

    /**
     * @param array<string, mixed> $engineOverrides
     */
    private function makePublisher(GitHubApiTransport $transport, array $engineOverrides = []): GitHubApiPublisher
    {
        [$settings, $reader] = $this->bootSettings($engineOverrides);

        return new GitHubApiPublisher(
            new GitPublishSettings($settings),
            new GitPathValidator(),
            $reader,
            $transport
        );
    }

    /**
     * @param array<string, mixed> $engineOverrides
     *
     * @return array{0: SettingsRepository, 1: FileReader}
     */
    private function bootSettings(array $engineOverrides): array
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
            'gitEnabled' => true,
            'gitPublishStrategy' => 'queued',
            'gitPublisher' => 'github_api',
            'gitGithubRepository' => 'acme/site',
            'gitGithubToken' => 'ghp_test_token',
            'gitBranch' => 'main',
            'gitCommitMessageTemplate' => 'content: publish {count} change(s)',
        ], $engineOverrides));

        return [$settings, $reader];
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

/**
 * @phpstan-type Call array{method: string, url: string, body: array<string, mixed>|null}
 */
final class RecordingGitHubTransport implements GitHubApiTransport
{
    /** @var list<Call> */
    public array $calls = [];

    public function request(string $method, string $url, string $token, ?array $body = null): array
    {
        $this->calls[] = ['method' => $method, 'url' => $url, 'body' => $body];

        if ($method === 'GET' && str_contains($url, '/git/ref/heads/')) {
            return ['object' => ['sha' => 'parentsha']];
        }
        if ($method === 'GET' && str_contains($url, '/git/commits/')) {
            return ['tree' => ['sha' => 'basetree']];
        }
        if ($method === 'POST' && str_contains($url, '/git/blobs')) {
            return ['sha' => 'blobsha'];
        }
        if ($method === 'POST' && str_contains($url, '/git/trees')) {
            return ['sha' => 'newtree'];
        }
        if ($method === 'POST' && str_contains($url, '/git/commits')) {
            return ['sha' => 'abc123commit'];
        }
        if ($method === 'PATCH') {
            return ['ref' => 'refs/heads/main'];
        }

        return [];
    }
}
