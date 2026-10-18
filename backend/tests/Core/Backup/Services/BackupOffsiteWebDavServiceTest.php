<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Backup\Services;

use PaginiumCMS\Core\Backup\Services\BackupOffsiteArchiveCipher;
use PaginiumCMS\Core\Backup\Services\BackupOffsiteWebDavService;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class BackupOffsiteWebDavServiceTest extends TestCase
{
    public function testDisabledWhenSettingsOff(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('backups')->willReturn([
            'offsiteWebDavEnabled' => false,
            'offsiteWebDavUrl' => 'https://cloud.example.com/remote.php/dav/files/user',
            'offsiteWebDavUsername' => 'user',
        ]);

        $service = new BackupOffsiteWebDavService($settings, OutboundUrlGuard::fromEnv(), new BackupOffsiteArchiveCipher());
        $result = $service->uploadZipIfConfigured('/tmp/missing.zip', 'backup.zip');

        $this->assertFalse($result['uploaded']);
        $this->assertSame('disabled_or_missing_file', $result['error'] ?? '');
    }
}
