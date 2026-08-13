<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\ShortcodeDefinitionPolicy;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Layout\Services\ShortcodeDefinitionManager;
use PaginiumCMS\Core\Layout\Services\ShortcodeRegistry;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class ShortcodeDefinitionManagerTest extends TestCase
{
    private string $baseDir;
    private ShortcodeDefinitionManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_shortcode_mgr_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $registry = new ShortcodeRegistry($reader, $writer, 'data/shortcodes/registry.json');

        $settings = new SettingsRepository(
            $writer,
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );

        $policyEngine = new CodePolicyEngine($settings, new SyntaxChecker(), new SecurityScanner());

        $this->manager = new ShortcodeDefinitionManager(
            new ShortcodeDefinitionPolicy(),
            $policyEngine,
            $registry,
            $reader,
            $writer
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testSaveAcceptsSafeDefinition(): void
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/hostile/shortcodes/safe-alert.json');
        $this->assertIsString($json);

        $saved = $this->manager->save('alert-box', $json);

        $this->assertSame('alert-box', $saved['name']);
        $this->assertTrue($saved['enabled']);
        $this->assertCount(1, $this->manager->list());
    }

    public function testSaveRejectsScriptTagFixture(): void
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/hostile/shortcodes/script-tag.json');
        $this->assertIsString($json);

        $this->expectException(CodePolicyViolationException::class);
        $this->manager->save('evil-script', $json);
    }

    public function testPreviewRejectsBadClassFixture(): void
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/hostile/shortcodes/bad-class.json');
        $this->assertIsString($json);

        $this->expectException(CodePolicyViolationException::class);
        $this->manager->preview($json);
    }

    public function testPreviewUsesSameGateAsSave(): void
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/hostile/shortcodes/safe-alert.json');
        $this->assertIsString($json);

        $preview = $this->manager->preview($json);
        $this->assertSame('alert-box', $preview['name']);

        $this->manager->save('alert-box', JsonHelper::encode($preview));
        $this->assertNotEmpty($this->manager->get('alert-box'));
    }

    public function testSaveAcceptsDynamicPgClassPlaceholder(): void
    {
        $json = JsonHelper::encode([
            'name' => 'alert-box',
            'version' => 1,
            'attrs' => [
                'tone' => [
                    'type' => 'enum',
                    'options' => ['info', 'warn'],
                ],
            ],
            'expand' => '<div class="pg-alert pg-alert-{{tone}}"><div class="pg-alert-body">{{content}}</div></div>',
        ], JSON_UNESCAPED_UNICODE);

        $saved = $this->manager->save('alert-box', $json);

        $this->assertSame('alert-box', $saved['name']);
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
