<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Firewall;

use PaginiumCMS\Core\Security\Firewall\FirewallBanStore;
use PaginiumCMS\Core\Security\Firewall\FirewallScenarioRegistry;
use PaginiumCMS\Core\Security\Firewall\FirewallScanner;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PHPUnit\Framework\TestCase;

final class FirewallScannerTest extends TestCase
{
    private FirewallScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new FirewallScanner(new FirewallScenarioRegistry());
    }

    public function testDetectsWordPressProbe(): void
    {
        $match = $this->scanner->scan('/wp-admin/setup-config.php', '', 'Mozilla/5.0');
        $this->assertNotNull($match);
        $this->assertSame('wp_probe', $match['id'] ?? null);
    }

    public function testDetectsEnvProbe(): void
    {
        $match = $this->scanner->scan('/.env', '', 'curl/8.0');
        $this->assertNotNull($match);
        $this->assertSame('env_probe', $match['id'] ?? null);
    }

    public function testDetectsPathTraversalInQuery(): void
    {
        $match = $this->scanner->scan('/api/media/file', 'path=..%2f..%2fetc/passwd', 'bot');
        $this->assertNotNull($match);
        $this->assertSame('path_traversal', $match['id'] ?? null);
    }

    public function testDoesNotFlagNormalArticlePath(): void
    {
        $match = $this->scanner->scan('/blog/my-article-slug', '', 'Mozilla/5.0 Chrome');
        $this->assertNull($match);
    }

    public function testDetectsEmptyUserAgentScenario(): void
    {
        $match = $this->scanner->scan('/api/health', '', '');
        $this->assertNotNull($match);
        $this->assertSame('bad_bot_ua', $match['id'] ?? null);
    }

    public function testDetectsSqlInjectionInRequestBody(): void
    {
        $body = '{"email":"admin@test.com","password":"x OR 1=1"}';
        $match = $this->scanner->scan('/api/auth/login', '', 'Mozilla/5.0', $body, true);

        $this->assertNotNull($match);
        $this->assertSame('sql_probe_body', $match['id'] ?? null);
    }

    public function testDetectsPathTraversalInRequestBody(): void
    {
        $body = '{"path":"../../etc/passwd"}';
        $match = $this->scanner->scan('/api/media/file', '', 'Mozilla/5.0', $body, true);

        $this->assertNotNull($match);
        $this->assertSame('path_traversal', $match['id'] ?? null);
    }

    public function testDetectsSsrfSchemeInRequestBody(): void
    {
        $body = '{"webhookUrl":"file:///etc/passwd"}';
        $match = $this->scanner->scan('/api/admin/settings', '', 'Mozilla/5.0', $body, true);

        $this->assertNotNull($match);
        $this->assertSame('ssrf_probe_body', $match['id'] ?? null);
    }

    public function testSkipsBodyTargetsWhenScanBodyDisabled(): void
    {
        $body = '{"password":"x OR 1=1"}';
        $match = $this->scanner->scan('/api/auth/login', '', 'Mozilla/5.0', $body, false);

        $this->assertNull($match);
    }

    public function testDoesNotScanBodyOnContentEditorPathWhenMiddlewarePolicyExempt(): void
    {
        $policy = new \PaginiumCMS\Core\Security\Firewall\FirewallBodyScanPolicy();
        $this->assertFalse($policy->shouldScan('POST', '/api/pages', true));

        $body = '# Tutorial\n\n```sql\nSELECT * FROM users\n```';
        $match = $this->scanner->scan('/api/pages', '', 'Mozilla/5.0', $body, false);
        $this->assertNull($match);
    }
}

final class FirewallBanStoreTest extends TestCase
{
    private string $basePath;
    private FirewallBanStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . '/paginium-firewall-' . uniqid('', true);
        mkdir($this->basePath . '/data/security/firewall', 0777, true);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn($this->basePath);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn([
            'jailMinutes' => 15,
            'maxRetries' => 3,
            'permanentThreshold' => 3,
        ]);

        $this->store = new FirewallBanStore($reader, $settings);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->basePath);
        parent::tearDown();
    }

    public function testJailAfterMaxRetries(): void
    {
        $ip = '203.0.113.50';

        $this->assertFalse($this->store->isBanned($ip));

        $first = $this->store->recordViolation($ip, 'wp_probe');
        $this->assertFalse($first['banned']);

        $second = $this->store->recordViolation($ip, 'wp_probe');
        $this->assertFalse($second['banned']);

        $third = $this->store->recordViolation($ip, 'wp_probe');
        $this->assertTrue($third['banned']);
        $this->assertTrue($this->store->isBanned($ip));
    }

    public function testWhitelistBypassesBanCheck(): void
    {
        $ip = '203.0.113.51';
        $this->store->applyManualBan($ip, true, 'manual');
        $this->store->addWhitelist($ip);

        $this->assertFalse($this->store->isBanned($ip));
        $this->assertTrue($this->store->isWhitelisted($ip));
    }

    public function testUnbanRemovesEntry(): void
    {
        $ip = '203.0.113.52';
        $this->store->applyManualBan($ip, false, 'manual');
        $this->assertTrue($this->store->isBanned($ip));

        $this->assertTrue($this->store->unban($ip));
        $this->assertFalse($this->store->isBanned($ip));
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
