<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

/**
 * Testy flat-file úložiska nastavení (Iterácia 4).
 * Reálny dočasný adresár – SettingsRepository používa fopen('c+') + flock.
 */
class SettingsRepositoryTest extends TestCase
{
    private string $baseDir;
    private SettingsRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_settings_test_' . uniqid();
        mkdir($this->baseDir, 0777, true);

        $this->repo = $this->makeRepo();
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testDefaultsWhenNothingStored(): void
    {
        $all = $this->repo->all();

        $this->assertSame('PaginiumCMS', $all['general']['siteName']);
        $this->assertSame(20, $all['content']['itemsPerPage']);
        $this->assertSame('off', $all['maintenance']['mode']);
    }

    public function testGetDotNotation(): void
    {
        $this->assertSame('markdown', $this->repo->get('editor.defaultEditor'));
        $this->assertSame('fallback', $this->repo->get('editor.missing', 'fallback'));
        $this->assertIsArray($this->repo->get('general'));
    }

    public function testSetGroupPersistsOverrideAndCoerces(): void
    {
        $result = $this->repo->setGroup('content', [
            'itemsPerPage' => '30',
            'storageFormat' => 'md',
            'defaultStatus' => 'published',
            'autoSaveInterval' => 120,
            'lockTtl' => 600,
        ]);

        $this->assertSame(30, $result['itemsPerPage'], 'Hodnota sa má pretypovať na int');
        $this->assertSame('published', $result['defaultStatus']);

        // Nová inštancia nad tým istým adresárom musí vidieť zmenu.
        $fresh = $this->makeRepo();
        $this->assertSame(30, $fresh->get('content.itemsPerPage'));
        // Iné skupiny ostávajú na predvolených hodnotách.
        $this->assertSame('PaginiumCMS', $fresh->get('general.siteName'));
    }

    public function testSetGroupRejectsInvalidValues(): void
    {
        $this->expectException(ValidationException::class);

        $this->repo->setGroup('content', [
            'itemsPerPage' => '999', // max:100
            'storageFormat' => 'md',
            'defaultStatus' => 'draft',
            'autoSaveInterval' => 60,
            'lockTtl' => 300,
        ]);
    }

    public function testUnknownGroupThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->repo->setGroup('nonexistent', ['foo' => 'bar']);
    }

    public function testUnknownKeysAreIgnored(): void
    {
        $result = $this->repo->setGroup('general', [
            'siteName' => 'Moja stránka',
            'language' => 'en',
            'timezone' => 'Europe/Prague',
            'evilKey' => 'hacker', // nie je v schéme → musí sa zahodiť
        ]);

        $this->assertArrayNotHasKey('evilKey', $result);
        $this->assertSame('Moja stránka', $result['siteName']);
    }

    public function testReset(): void
    {
        $this->repo->setGroup('general', [
            'siteName' => 'Zmenené',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);
        $this->assertSame('Zmenené', $this->repo->get('general.siteName'));

        $this->repo->reset();

        $this->assertSame('PaginiumCMS', $this->repo->get('general.siteName'));
    }

    public function testSecretFieldsAreEncryptedAtRest(): void
    {
        // Audit A1: citlivé polia (typ password) sa do settings.json ukladajú
        // zašifrované, no cez repository sa čítajú v plaintexte (transparentne).
        $encryption = new EncryptionService('base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=');
        $repo = $this->makeRepo($encryption);

        $repo->setGroup('smtp', ['password' => 'super-smtp-secret']);

        $raw = (string) file_get_contents($this->baseDir . '/data/settings.json');
        $this->assertStringNotContainsString('super-smtp-secret', $raw, 'Plaintext secret nesmie byť na disku');
        $this->assertStringContainsString('enc:', $raw, 'Secret má byť zašifrovaný');

        // Nová inštancia (nad tým istým súborom) musí vrátiť plaintext.
        $fresh = $this->makeRepo($encryption);
        $this->assertSame('super-smtp-secret', $fresh->get('smtp.password'));
    }

    public function testNonSecretFieldsRemainPlaintext(): void
    {
        $encryption = new EncryptionService('base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=');
        $repo = $this->makeRepo($encryption);

        $repo->setGroup('general', ['siteName' => 'Verejný názov', 'language' => 'en', 'timezone' => 'UTC']);

        $raw = (string) file_get_contents($this->baseDir . '/data/settings.json');
        // Nešifrované pole ostáva čitateľné (nie je citlivé).
        $this->assertStringContainsString('Verejný názov', $raw);
    }

    public function testLegacyMaintenanceModeMigratesToUnderMaintenance(): void
    {
        $settingsPath = $this->baseDir . '/data/settings.json';
        if (!is_dir(dirname($settingsPath))) {
            mkdir(dirname($settingsPath), 0777, true);
        }

        file_put_contents($settingsPath, json_encode([
            'general' => ['maintenanceMode' => true],
        ], JSON_THROW_ON_ERROR));

        $fresh = $this->makeRepo();
        $this->assertSame('under_maintenance', $fresh->get('maintenance.mode'));
    }

    public function testMaintenanceHeroImageUrlAcceptsStoragePath(): void
    {
        $storagePath = '/storage/app/content/media/very-long-maintenance-background-filename-2026.png';

        $this->repo->setGroup('maintenance', [
            'mode' => 'under_maintenance',
            'heroImageUrl' => $storagePath,
        ]);

        $this->assertSame($storagePath, $this->repo->get('maintenance.heroImageUrl'));
    }

    private function makeRepo(?EncryptionService $encryption = null): SettingsRepository
    {
        $validator = new FileValidator($this->baseDir);

        return new SettingsRepository(
            new FileWriter($validator),
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json',
            $encryption
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
