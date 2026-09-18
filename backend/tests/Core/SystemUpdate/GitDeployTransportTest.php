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
        parent::tearDown();
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
