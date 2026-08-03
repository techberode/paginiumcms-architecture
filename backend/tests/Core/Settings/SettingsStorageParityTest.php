<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Storage\StorageFactory;
use PaginiumCMS\Core\Validation\DocumentSchemaRegistry;
use PaginiumCMS\Core\Validation\DocumentValidator;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

/**
 * Regression: settings persisted through StorageInterface match legacy file output.
 */
final class SettingsStorageParityTest extends TestCase
{
    private string $baseDir;
    private SettingsRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_settings_parity_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $storage = StorageTestHelper::localStorage($this->baseDir);
        $documentValidator = new DocumentValidator(DocumentSchemaRegistry::createWithDefaults());

        $this->repo = new SettingsRepository(
            new FileWriter($validator),
            $storage,
            new Validator(),
            'data/settings.json',
            null,
            $documentValidator
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testMissingEngineGroupUsesClassicDefaults(): void
    {
        $engine = $this->repo->group('engine');

        $this->assertSame('classic', $engine['deploymentMode']);
        $this->assertSame('local', $engine['storageDriver']);
        $this->assertTrue($engine['schemaValidationEnabled']);
        $this->assertSame('local', StorageFactory::driverFromEngineSettings([]));
        $this->assertSame('classic', StorageFactory::deploymentModeFromEngineSettings([]));
    }

    public function testStorageReadMatchesDirectFileAfterSetGroup(): void
    {
        $this->repo->setGroup('general', [
            'siteName' => 'Parity Site',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $storage = StorageTestHelper::localStorage($this->baseDir);
        $rawFromStorage = $storage->read('data/settings.json');

        $validator = new FileValidator($this->baseDir);
        $rawFromReader = (new FileReader($validator))->read('data/settings.json');

        $this->assertSame($rawFromReader, $rawFromStorage);
        $this->assertStringContainsString('Parity Site', $rawFromStorage);
    }

    public function testInvalidOverridesDocumentFailsClosed(): void
    {
        file_put_contents(
            $this->baseDir . '/data/settings.json',
            json_encode(['general' => 'not-an-object'], JSON_THROW_ON_ERROR)
        );

        $this->expectException(\PaginiumCMS\Core\Validation\ValidationException::class);

        $this->repo->setGroup('general', [
            'siteName' => 'Should Fail',
            'language' => 'sk',
            'timezone' => 'Europe/Bratislava',
        ]);
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
