<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
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
        $this->assertFalse($all['general']['maintenanceMode']);
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

    private function makeRepo(): SettingsRepository
    {
        $validator = new FileValidator($this->baseDir);

        return new SettingsRepository(
            new FileReader($validator),
            new FileWriter($validator),
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
