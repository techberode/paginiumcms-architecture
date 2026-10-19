<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Backup\Commands;

use PaginiumCMS\Core\Backup\Commands\BackupDecryptOffsiteCommand;
use PaginiumCMS\Core\Backup\Services\BackupOffsiteArchiveCipher;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class BackupDecryptOffsiteCommandTest extends TestCase
{
    /** @var list<string> */
    private array $cleanup = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $path) {
            @unlink($path);
        }
    }

    public function testMissingInputFails(): void
    {
        $tester = new CommandTester($this->command('unused'));

        $this->assertSame(Command::FAILURE, $tester->execute(['input' => '/nonexistent/backup.zip.enc']));
    }

    public function testRefusesToOverwriteWithoutForce(): void
    {
        $input = $this->temp('x.zip.enc', 'enc:bk:s2:');
        $this->temp('x.zip', 'existing');

        $tester = new CommandTester($this->command('pass'));

        $this->assertSame(Command::FAILURE, $tester->execute(['input' => $input]));
        $this->assertStringContainsString('--force', $tester->getDisplay());
    }

    public function testDecryptsWithStoredSettingsKey(): void
    {
        if (!function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push')) {
            $this->markTestSkipped('libsodium not available');
        }

        $cipher = new BackupOffsiteArchiveCipher(BackupOffsiteArchiveCipher::KDF_OPS_MIN, BackupOffsiteArchiveCipher::KDF_MEM_MIN);
        $plain = $this->temp('plain.zip', 'PK restore me');
        $encrypted = $cipher->encryptZipFile($plain, 'backup.zip', 'stored-key');
        $this->cleanup[] = $encrypted['path'];
        $out = $this->path('restored.zip');

        $tester = new CommandTester($this->command('stored-key', $cipher));
        $code = $tester->execute(['input' => $encrypted['path'], 'output' => $out, '--from-settings' => true]);

        $this->assertSame(Command::SUCCESS, $code, $tester->getDisplay());
        $this->assertSame('PK restore me', file_get_contents($out));
    }

    private function command(string $storedKey, ?BackupOffsiteArchiveCipher $cipher = null): BackupDecryptOffsiteCommand
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn(['offsiteEncryptionKey' => $storedKey]);

        return new BackupDecryptOffsiteCommand($cipher ?? new BackupOffsiteArchiveCipher(), $settings);
    }

    private function temp(string $suffix, string $contents): string
    {
        $path = $this->path($suffix);
        file_put_contents($path, $contents);

        return $path;
    }

    private function path(string $suffix): string
    {
        static $prefix = null;
        $prefix ??= sys_get_temp_dir() . '/paginium-decrypt-cmd-' . bin2hex(random_bytes(4)) . '-';
        $path = $prefix . $suffix;
        $this->cleanup[] = $path;

        return $path;
    }
}
