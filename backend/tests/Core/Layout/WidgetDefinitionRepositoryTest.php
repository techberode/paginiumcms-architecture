<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Layout\Services\WidgetCatalog;
use PaginiumCMS\Core\Layout\Services\WidgetDefinitionPolicy;
use PaginiumCMS\Core\Layout\Services\WidgetDefinitionRepository;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WidgetDefinitionRepositoryTest extends TestCase
{
    private string $baseDir;
    private WidgetDefinitionRepository $repository;
    private WidgetCatalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_widget_def_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $settings = new SettingsRepository(
            $writer,
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );
        $engine = new CodePolicyEngine($settings, new SyntaxChecker(), new SecurityScanner());

        $this->repository = new WidgetDefinitionRepository(
            new WidgetDefinitionPolicy(),
            $engine,
            $reader,
            $writer
        );
        $this->catalog = new WidgetCatalog($this->repository);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testSaveAndExpandCustomWidget(): void
    {
        $saved = $this->repository->save('promo', [
            'id' => 'promo',
            'label' => 'Promo card',
            'selfClosing' => true,
            'fields' => [
                ['key' => 'title', 'kind' => 'string'],
            ],
            'defaults' => ['title' => 'Hello'],
            'expand' => '<div class="pg-widget pg-widget-custom"><p class="pg-widget-title">{{title}}</p></div>',
        ], $this->catalog->builtinIds());

        $this->assertSame('promo', $saved['id']);
        $this->assertContains('promo', array_map(
            static fn (array $row): string => (string) $row['id'],
            $this->catalog->catalog()
        ));

        $html = $this->catalog->render(' type="promo" title="Visitors <b>"', '');
        $this->assertStringContainsString('pg-widget-custom', $html);
        $this->assertStringContainsString('Visitors &lt;b&gt;', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function testRejectsScriptAndReservedId(): void
    {
        $this->expectException(CodePolicyViolationException::class);
        $this->repository->save('evil', [
            'id' => 'evil',
            'fields' => [],
            'expand' => '<div class="pg-widget"><script>alert(1)</script></div>',
        ], $this->catalog->builtinIds());
    }

    public function testCannotOverwriteBuiltin(): void
    {
        $this->expectException(RuntimeException::class);
        $this->repository->save('kpi', [
            'id' => 'kpi',
            'fields' => [],
            'expand' => '<div class="pg-widget">x</div>',
        ], $this->catalog->builtinIds());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
