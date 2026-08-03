<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Notification;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Notification\Services\NotificationFactory;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class NotificationFactoryTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/paginium_notification_' . uniqid();
        mkdir($this->baseDir . '/data', 0777, true);
        chdir($this->baseDir);
    }

    protected function tearDown(): void
    {
        chdir(sys_get_temp_dir());
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testCreatesEmailAdapterWhenSmtpEnabled(): void
    {
        $repo = $this->makeRepo();
        $repo->setGroup('smtp', [
            'enabled' => true,
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'fromEmail' => 'cms@example.com',
            'fromName' => 'PaginiumCMS',
        ]);
        $repo->setGroup('connectors', ['emailEnabled' => true, 'ntfyAuthMode' => 'none']);

        $service = NotificationFactory::create($repo);

        $this->assertContains('email', $service->getAdapters());
    }

    public function testConnectorOverviewListsAllChannels(): void
    {
        $repo = $this->makeRepo();
        $overview = NotificationFactory::connectorOverview($repo);

        $names = array_column($overview, 'name');
        $this->assertContains('email', $names);
        $this->assertContains('ntfy', $names);
        $this->assertContains('discord', $names);
        $this->assertContains('telegram', $names);
        $this->assertContains('webhook', $names);
    }

    public function testNtfyRequiresTokenWhenAuthModeToken(): void
    {
        $repo = $this->makeRepo();
        $repo->setGroup('connectors', [
            'ntfyEnabled' => true,
            'ntfyTopic' => 'alerts',
            'ntfyAuthMode' => 'token',
            'ntfyAccessToken' => '',
        ]);

        $service = NotificationFactory::create($repo);
        $this->assertNotContains('ntfy', $service->getAdapters());

        $overview = NotificationFactory::connectorOverview($repo);
        $ntfy = $this->connectorByName($overview, 'ntfy');
        $this->assertTrue($ntfy['configured']);
        $this->assertFalse($ntfy['authenticated']);
        $this->assertFalse($ntfy['enabled']);
    }

    public function testNtfyAdapterRegisteredWithBearerToken(): void
    {
        $repo = $this->makeRepo();
        $repo->setGroup('connectors', [
            'ntfyEnabled' => true,
            'ntfyTopic' => 'alerts',
            'ntfyAuthMode' => 'token',
            'ntfyAccessToken' => 'tk_test_abc',
        ]);

        $service = NotificationFactory::create($repo);
        $this->assertContains('ntfy', $service->getAdapters());

        $overview = NotificationFactory::connectorOverview($repo);
        $ntfy = $this->connectorByName($overview, 'ntfy');
        $this->assertTrue($ntfy['authenticated']);
        $this->assertTrue($ntfy['enabled']);
        $this->assertSame('token', $ntfy['auth_mode']);
    }

    public function testConnectorAuthErrorForMissingNtfyToken(): void
    {
        $connectors = [
            'ntfyEnabled' => true,
            'ntfyTopic' => 'alerts',
            'ntfyAuthMode' => 'token',
            'ntfyAccessToken' => '',
        ];

        $message = NotificationFactory::connectorAuthError('ntfy', $connectors);
        $this->assertNotNull($message);
        $this->assertStringContainsString('access token', strtolower($message));
    }

    /**
     * @param list<array<string, mixed>> $overview
     * @return array<string, mixed>
     */
    private function connectorByName(array $overview, string $name): array
    {
        foreach ($overview as $row) {
            if (($row['name'] ?? '') === $name) {
                return $row;
            }
        }

        $this->fail('Connector not found: ' . $name);
    }

    private function makeRepo(): SettingsRepository
    {
        $validator = new FileValidator($this->baseDir);

        return new SettingsRepository(
            new FileWriter($validator),
            \PaginiumCMS\Tests\Support\StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
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
