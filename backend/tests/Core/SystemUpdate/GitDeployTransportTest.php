<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\SystemUpdate;

use PaginiumCMS\Core\SystemUpdate\Services\GitDeployTransport;
use PaginiumCMS\Tests\Http\TestCase;

final class GitDeployTransportTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('GITHUB_DEPLOY_TOKEN');
        unset($_ENV['GITHUB_DEPLOY_TOKEN']);
        putenv('GITHUB_DEPLOY_SSH_KEY_PATH');
        unset($_ENV['GITHUB_DEPLOY_SSH_KEY_PATH']);
        parent::tearDown();
    }

    public function testResolveDeploySshKeyPathIgnoresUnreadableEnvAndUsesFallbackIfPresent(): void
    {
        putenv('GITHUB_DEPLOY_SSH_KEY_PATH=/this/path/does/not/exist/github_deploy_key');
        $_ENV['GITHUB_DEPLOY_SSH_KEY_PATH'] = '/this/path/does/not/exist/github_deploy_key';

        $resolved = GitDeployTransport::resolveDeploySshKeyPath();
        $this->assertTrue(
            $resolved === null
            || $resolved === '/run/secrets/github_deploy_key'
            || $resolved === '/var/lib/paginiumcms/secrets/github_deploy_key'
        );
    }

    public function testDiagnoseDeploySshKeyReportsMissingEnvPath(): void
    {
        putenv('GITHUB_DEPLOY_SSH_KEY_PATH=/this/path/does/not/exist/github_deploy_key');
        $_ENV['GITHUB_DEPLOY_SSH_KEY_PATH'] = '/this/path/does/not/exist/github_deploy_key';

        $diagnosis = GitDeployTransport::diagnoseDeploySshKey();
        if ($diagnosis['configured']) {
            $this->assertSame('ok', $diagnosis['status']);

            return;
        }

        $this->assertSame('missing', $diagnosis['status']);
        $this->assertSame('/this/path/does/not/exist/github_deploy_key', $diagnosis['env_path']);
        $this->assertStringContainsString('not inside the PHP container', $diagnosis['detail']);
    }

    public function testResolveDeploySshKeyPathUsesReadableEnvPath(): void
    {
        $key = tempnam(sys_get_temp_dir(), 'paginium-deploy-key-');
        $this->assertNotFalse($key);
        file_put_contents($key, "-----BEGIN OPENSSH PRIVATE KEY-----\ntest\n");
        chmod($key, 0640);
        putenv('GITHUB_DEPLOY_SSH_KEY_PATH=' . $key);
        $_ENV['GITHUB_DEPLOY_SSH_KEY_PATH'] = $key;

        $this->assertSame($key, GitDeployTransport::resolveDeploySshKeyPath());
        @unlink($key);
    }

    public function testPrefersDeployKeySshWhenKeyFileReadable(): void
    {
        $key = tempnam(sys_get_temp_dir(), 'paginium-deploy-key-');
        $this->assertNotFalse($key);
        file_put_contents($key, "-----BEGIN OPENSSH PRIVATE KEY-----\ntest\n");
        chmod($key, 0640);
        putenv('GITHUB_DEPLOY_SSH_KEY_PATH=' . $key);
        $_ENV['GITHUB_DEPLOY_SSH_KEY_PATH'] = $key;

        $this->assertSame(
            GitDeployTransport::hasSshBinary(),
            GitDeployTransport::prefersDeployKeySsh()
        );
        @unlink($key);
    }

    public function testResolveGithubKnownHostsFileFindsBundledKeys(): void
    {
        $path = GitDeployTransport::resolveGithubKnownHostsFile();
        $this->assertNotNull($path);
        $this->assertFileExists($path);
        $this->assertStringContainsString('github.com ssh-ed25519', (string) file_get_contents($path));
        $this->assertStringContainsString('UserKnownHostsFile', GitDeployTransport::githubSshBaseCommand());
    }

    public function testResolveGithubDeployTokenPrefersSettings(): void
    {
        putenv('GITHUB_DEPLOY_TOKEN=from_env');

        $token = GitDeployTransport::resolveGithubDeployToken([
            'githubToken' => 'from_settings',
        ]);

        $this->assertSame('from_settings', $token);
    }

    public function testResolveGithubDeployTokenFallsBackToEnv(): void
    {
        putenv('GITHUB_DEPLOY_TOKEN=from_env');

        $token = GitDeployTransport::resolveGithubDeployToken([
            'githubToken' => '',
        ]);

        $this->assertSame('from_env', $token);
    }

    public function testResolveGithubDeployTokenIgnoresMaskPlaceholder(): void
    {
        putenv('GITHUB_DEPLOY_TOKEN=from_env');

        $token = GitDeployTransport::resolveGithubDeployToken([
            'githubToken' => '********',
        ]);

        $this->assertSame('from_env', $token);
    }
}
