<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Backup\Services;

use PaginiumCMS\Core\Backup\Services\BackupOffsiteArchiveCipher;
use PHPUnit\Framework\TestCase;

final class BackupOffsiteArchiveCipherTest extends TestCase
{
    public function testEncryptProducesEncFile(): void
    {
        if (!function_exists('sodium_crypto_secretbox')) {
            $this->markTestSkipped('libsodium not available');
        }

        $plain = sys_get_temp_dir() . '/paginium-plain-' . uniqid('', true) . '.zip';
        file_put_contents($plain, 'PK fake zip payload');

        $cipher = new BackupOffsiteArchiveCipher();
        $result = $cipher->encryptZipFile($plain, 'backup_2026.zip', 'test-passphrase');

        $this->assertFileExists($result['path']);
        $this->assertSame('backup_2026.zip.enc', $result['remoteName']);
        $this->assertStringStartsWith('enc:bk:s1:', (string) file_get_contents($result['path']));

        @unlink($plain);
        @unlink($result['path']);
    }
}
