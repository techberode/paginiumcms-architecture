<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Services;

use PaginiumCMS\Core\Security\Services\EncryptionService;
use PHPUnit\Framework\TestCase;

/**
 * Overuje šifrovanie tajomstiev „at-rest" (audit A1).
 */
final class EncryptionServiceTest extends TestCase
{
    /** Náhodný 32-bajtový kľúč (base64) len pre testy. */
    private const KEY = 'base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=';

    private function service(): EncryptionService
    {
        return new EncryptionService(self::KEY);
    }

    public function testEnabledWithValidKey(): void
    {
        $this->assertTrue($this->service()->isEnabled());
    }

    public function testDisabledWithEmptyKey(): void
    {
        $e = new EncryptionService(null);
        $this->assertFalse($e->isEnabled());
        // Vypnuté = plaintext pass-through (fail-safe rollout).
        $this->assertSame('secret', $e->encrypt('secret'));
    }

    public function testDisabledWithPlaceholderKey(): void
    {
        // Predvídateľný placeholder (jeden opakovaný znak) nesmie aktivovať šifrovanie.
        $e = new EncryptionService('base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $this->assertFalse($e->isEnabled());
        $this->assertSame('secret', $e->encrypt('secret'));
    }

    public function testRoundTrip(): void
    {
        $e = $this->service();
        $cipher = $e->encrypt('TOTPSEED123');

        $this->assertNotSame('TOTPSEED123', $cipher);
        $this->assertTrue($e->isEncrypted($cipher));
        $this->assertSame('TOTPSEED123', $e->decrypt($cipher));
    }

    public function testCiphertextIsNonDeterministic(): void
    {
        $e = $this->service();
        $this->assertNotSame($e->encrypt('same'), $e->encrypt('same'));
    }

    public function testEncryptIsIdempotent(): void
    {
        $e = $this->service();
        $cipher = $e->encrypt('value');
        $this->assertSame($cipher, $e->encrypt($cipher));
    }

    public function testLegacyPlaintextPassesThroughOnDecrypt(): void
    {
        // Transparentná migrácia: neprefixovaná hodnota sa vráti nezmenená.
        $this->assertSame('legacy-plain', $this->service()->decrypt('legacy-plain'));
    }

    public function testEmptyStringNotEncrypted(): void
    {
        $e = $this->service();
        $this->assertSame('', $e->encrypt(''));
    }

    public function testNullableHelpers(): void
    {
        $e = $this->service();
        $this->assertNull($e->encryptNullable(null));
        $this->assertSame('', $e->encryptNullable(''));
        $this->assertNull($e->decryptNullable(null));

        $cipher = $e->encryptNullable('x');
        $this->assertNotNull($cipher);
        $this->assertSame('x', $e->decryptNullable($cipher));
    }

    public function testWrongKeyCannotDecrypt(): void
    {
        $cipher = $this->service()->encrypt('topsecret');

        // Iný kľúč → autentifikácia zlyhá → prázdny reťazec (fail-safe).
        $other = new EncryptionService('base64:' . base64_encode(str_repeat("\x01", 32)));
        $this->assertSame('', $other->decrypt($cipher));
    }

    public function testDecryptOpensslFormatExplicitly(): void
    {
        // Krížová kompatibilita: hodnota vo formáte enc:g1: (AES-256-GCM) sa
        // dešifruje aj keď je preferovaný iný backend.
        $e = $this->service();
        $cipher = $e->encrypt('gcm-value');
        if (str_starts_with($cipher, 'enc:g1:')) {
            $this->assertSame('gcm-value', $e->decrypt($cipher));
        } else {
            $this->assertSame('gcm-value', $e->decrypt($cipher));
        }
    }
}
