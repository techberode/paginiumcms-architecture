<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\I18n;

use PaginiumCMS\Core\CodeEditor\Services\FileBackup;
use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\I18n\Services\LocaleScaffoldService;
use PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry;
use PaginiumCMS\Core\I18n\Services\TranslationFileManager;
use PaginiumCMS\Core\I18n\Services\TranslationPolicyValidator;
use PHPUnit\Framework\TestCase;

final class TranslationFileManagerTest extends TestCase
{
    private TranslationFileManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $root = dirname(__DIR__, 4);
        $locales = new SupportedLocalesRegistry($root);
        $this->manager = new TranslationFileManager(
            new TranslationPolicyValidator(new SyntaxChecker()),
            $locales,
            new LocaleScaffoldService($locales, $root),
            new FileBackup($root),
            $root
        );
    }

    public function testAllowsBackendLangFiles(): void
    {
        $this->assertTrue($this->manager->canEdit('backend/lang/sk/content.php'));
        $this->assertTrue($this->manager->canEdit('backend/lang/en/media.php'));
    }

    public function testAllowsFrontendI18nFiles(): void
    {
        $this->assertTrue($this->manager->canEdit('frontend/src/i18n/core/sk.ts'));
        $this->assertTrue($this->manager->canEdit('frontend/src/i18n/modules/admin/en.ts'));
    }

    public function testDeniesPathsOutsideCatalog(): void
    {
        $this->assertFalse($this->manager->canEdit('backend/app/Core/Config/ConfigManager.php'));
        $this->assertFalse($this->manager->canEdit('backend/lang/sk/../content.php'));
        $this->assertFalse($this->manager->canEdit('frontend/src/App.tsx'));
    }

    public function testListCatalogIncludesKnownFiles(): void
    {
        $paths = array_column($this->manager->listCatalog()['files'], 'path');

        $this->assertContains('backend/lang/sk/content.php', $paths);
        $this->assertContains('frontend/src/i18n/modules/admin/sk.ts', $paths);
    }

    public function testReadExistingBackendLangFile(): void
    {
        $content = $this->manager->readFile('backend/lang/sk/content.php');

        $this->assertStringContainsString("'not_found'", $content);
        $this->assertStringContainsString('return [', $content);
    }
}
