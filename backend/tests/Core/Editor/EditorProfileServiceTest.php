<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\EditorComponentRegistry;
use PaginiumCMS\Core\Editor\Services\EditorProfileService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Extensions\Contracts\PluginManagerInterface;
use PHPUnit\Framework\TestCase;

final class EditorProfileServiceTest extends TestCase
{
    private EditorProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $baseDir = sys_get_temp_dir() . '/paginium_profiles_' . uniqid();
        mkdir($baseDir . '/data', 0777, true);
        chdir($baseDir);

        $validator = new FileValidator($baseDir);
        $settings = new SettingsRepository(
            new FileWriter($validator),
            \PaginiumCMS\Tests\Support\StorageTestHelper::localStorage($baseDir),
            new Validator(),
            'data/settings.json'
        );
        $plugins = $this->createMock(PluginManagerInterface::class);
        $plugins->method('listEnabledEditorComponents')->willReturn([]);
        $components = new EditorComponentRegistry($plugins);
        $this->service = new EditorProfileService($settings, $components);
    }

    public function testListsBuiltInProfiles(): void
    {
        $ids = array_map(static fn ($profile) => $profile->id, $this->service->listProfiles());

        $this->assertContains('company', $ids);
        $this->assertContains('blog', $ids);
        $this->assertContains('minimal', $ids);
        $this->assertContains('developer', $ids);
    }

    public function testDefaultProfileByContentType(): void
    {
        $this->assertSame('company', $this->service->resolveDefaultProfileId('page'));
        $this->assertSame('blog', $this->service->resolveDefaultProfileId('article'));
    }
}
