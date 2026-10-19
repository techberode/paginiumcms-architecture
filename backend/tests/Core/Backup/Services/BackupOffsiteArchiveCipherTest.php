<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Backup\Services;

use PaginiumCMS\Core\Backup\Services\BackupOffsiteArchiveCipher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BackupOffsiteArchiveCipherTest extends TestCase
{
    /** @var list<string> */
    private array $cleanup = [];

    protected function setUp(): void
    {
        if (!function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push')) {
            $this->markTestSkipped('libsodium not available');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $path) {
            @unlink($path);
        }
    }

    public function testEncryptProducesSaltedV2File(): void
    {
        $plain = $this->tempFile('PK fake zip payload');

        $result = $this->cipher()->encryptZipFile($plain, 'backup_2026.zip', 'test-passphrase');
        $this->cleanup[] = $result['path'];

        $this->assertSame('backup_2026.zip.enc', $result['remoteName']);
        $encrypted = (string) file_get_contents($result['path']);
        $this->assertStringStartsWith(BackupOffsiteArchiveCipher::PREFIX_V2, $encrypted);
        $this->assertStringNotContainsString('fake zip payload', $encrypted);
    }

    public function testSamePassphraseProducesDifferentCiphertexts(): void
    {
        $plain = $this->tempFile('identical payload');
        $a = $this->cipher()->encryptZipFile($plain, 'a.zip', 'pass');
        $b = $this->cipher()->encryptZipFile($plain, 'b.zip', 'pass');
        $this->cleanup[] = $a['path'];
        $this->cleanup[] = $b['path'];

        $this->assertNotSame(file_get_contents($a['path']), file_get_contents($b['path']));
    }

    public function testRoundTripMultiChunkArchive(): void
    {
        $payload = random_bytes(65536 * 3 + 123);
        $plain = $this->tempFile($payload);

        $result = $this->cipher()->encryptZipFile($plain, 'big.zip', 'correct horse');
        $this->cleanup[] = $result['path'];
        $out = $this->tempPath();
        $this->cipher()->decryptToFile($result['path'], $out, 'correct horse');

        $this->assertSame($payload, file_get_contents($out));
    }

    public function testWrongPassphraseFailsAndLeavesNoOutput(): void
    {
        $plain = $this->tempFile('secret zip');
        $result = $this->cipher()->encryptZipFile($plain, 'x.zip', 'right');
        $this->cleanup[] = $result['path'];
        $out = $this->tempPath();

        try {
            $this->cipher()->decryptToFile($result['path'], $out, 'wrong');
            $this->fail('Expected decryption failure');
        } catch (RuntimeException) {
            $this->assertFileDoesNotExist($out);
        }
    }

    public function testTamperedKdfOpsLimitIsRejected(): void
    {
        $plain = $this->tempFile('zip');
        $result = $this->cipher()->encryptZipFile($plain, 'x.zip', 'pass');
        $this->cleanup[] = $result['path'];
        $bytes = (string) file_get_contents($result['path']);
        $opsOffset = strlen(BackupOffsiteArchiveCipher::PREFIX_V2) + SODIUM_CRYPTO_PWHASH_SALTBYTES;
        $bytes = substr_replace($bytes, pack('N', 0x7FFFFFFF), $opsOffset, 4);
        file_put_contents($result['path'], $bytes);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid key derivation parameters.');
        $this->cipher()->decryptToFile($result['path'], $this->tempPath(), 'pass');
    }

    public function testTruncatedArchiveIsRejected(): void
    {
        $plain = $this->tempFile(random_bytes(65536 * 2));
        $result = $this->cipher()->encryptZipFile($plain, 'x.zip', 'pass');
        $this->cleanup[] = $result['path'];
        $bytes = (string) file_get_contents($result['path']);
        file_put_contents($result['path'], substr($bytes, 0, strlen($bytes) - 40));

        $this->expectException(RuntimeException::class);
        $this->cipher()->decryptToFile($result['path'], $this->tempPath(), 'pass');
    }

    public function testDecryptsLegacyV1Payload(): void
    {
        $nonce = random_bytes(24);
        $legacy = BackupOffsiteArchiveCipher::PREFIX_V1
            . base64_encode($nonce . sodium_crypto_secretbox('legacy zip', $nonce, hash('sha256', 'old-pass', true)));
        $encPath = $this->tempFile($legacy);
        $out = $this->tempPath();

        $this->cipher()->decryptToFile($encPath, $out, 'old-pass');

        $this->assertSame('legacy zip', file_get_contents($out));
    }

    private function cipher(): BackupOffsiteArchiveCipher
    {
        return new BackupOffsiteArchiveCipher(
            BackupOffsiteArchiveCipher::KDF_OPS_MIN,
            BackupOffsiteArchiveCipher::KDF_MEM_MIN
        );
    }

    private function tempFile(string $contents): string
    {
        $path = $this->tempPath();
        file_put_contents($path, $contents);

        return $path;
    }

    private function tempPath(): string
    {
        $path = sys_get_temp_dir() . '/paginium-cipher-test-' . bin2hex(random_bytes(6));
        $this->cleanup[] = $path;

        return $path;
    }
}
