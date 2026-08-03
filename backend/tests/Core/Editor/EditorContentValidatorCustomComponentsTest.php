<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Models\EditorComponentDefinition;
use PaginiumCMS\Core\Editor\Services\EditorComponentRegistry;
use PaginiumCMS\Core\Editor\Services\EditorContentValidator;
use PaginiumCMS\Core\Editor\Services\EditorProfileService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Extensions\Contracts\PluginManagerInterface;
use PHPUnit\Framework\TestCase;

final class EditorContentValidatorCustomComponentsTest extends TestCase
{
    private EditorContentValidator $validator;
    private SettingsRepository $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $baseDir = sys_get_temp_dir() . '/paginium_editor_custom_' . uniqid();
        mkdir($baseDir . '/data', 0777, true);
        chdir($baseDir);

        $fileValidator = new FileValidator($baseDir);
        $this->settings = new SettingsRepository(
            new FileWriter($fileValidator),
            \PaginiumCMS\Tests\Support\StorageTestHelper::localStorage($baseDir),
            new Validator(),
            'data/settings.json'
        );

        $definition = new EditorComponentDefinition(
            'hello-widget',
            'Hello Widget',
            'hello-widget',
            'hello-widget',
            'helloWidget'
        );

        $plugins = $this->createMock(PluginManagerInterface::class);
        $plugins->method('listEnabledEditorComponents')->willReturn([$definition]);
        $components = new EditorComponentRegistry($plugins);

        $profiles = new EditorProfileService($this->settings, $components);
        $this->validator = new EditorContentValidator($profiles, $components);
    }

    public function testRejectsUnknownMarkdownDirective(): void
    {
        $error = $this->validator->validate('page', [
            'content' => ":::unknown-block\nBody\n:::\n",
            'contentFormat' => 'markdown',
            'editorProfile' => 'blog',
        ]);

        $this->assertSame('Neznámy custom komponent v Markdown: unknown-block.', $error);
    }

    public function testRejectsDisallowedCustomComponentForProfile(): void
    {
        $error = $this->validator->validate('page', [
            'content' => ":::hello-widget\nBody\n:::\n",
            'contentFormat' => 'markdown',
            'editorProfile' => 'blog',
        ]);

        $this->assertSame('Custom komponent nie je povolený pre tento profil: hello-widget.', $error);
    }

    public function testAllowsConfiguredCustomMarkdownComponent(): void
    {
        $this->settings->setGroup('editor', [
            'customComponentsEnabled' => true,
            'profileCustomComponents' => json_encode(['blog' => ['hello-widget']], JSON_THROW_ON_ERROR),
        ]);

        $error = $this->validator->validate('article', [
            'content' => ":::hello-widget\nBody\n:::\n",
            'contentFormat' => 'markdown',
            'editorProfile' => 'blog',
        ]);

        $this->assertNull($error);
    }
}
