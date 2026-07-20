<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\EditorContentValidator;
use PaginiumCMS\Core\Editor\Services\EditorProfileService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class EditorContentValidatorTest extends TestCase
{
    private EditorContentValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $baseDir = sys_get_temp_dir() . '/paginium_editor_' . uniqid();
        mkdir($baseDir . '/data', 0777, true);
        chdir($baseDir);

        $validator = new FileValidator($baseDir);
        $settings = new SettingsRepository(
            new FileReader($validator),
            new FileWriter($validator),
            new Validator(),
            'data/settings.json'
        );
        $this->validator = new EditorContentValidator(new EditorProfileService($settings));
    }

    public function testMinimalProfileRejectsMarkdownImage(): void
    {
        $error = $this->validator->validate('page', [
            'content' => 'Text ![alt](/img.png)',
            'contentFormat' => 'markdown',
            'editorProfile' => 'minimal',
        ]);

        $this->assertSame('Profil editora nepovoľuje obrázky.', $error);
    }

    public function testBlogProfileRejectsHtmlTable(): void
    {
        $error = $this->validator->validate('article', [
            'content' => '<p>x</p><table><tr><td>1</td></tr></table>',
            'contentFormat' => 'html',
            'editorProfile' => 'blog',
        ]);

        $this->assertSame('Profil editora nepovoľuje tabuľky.', $error);
    }

    public function testDeveloperProfileAllowsCodeBlock(): void
    {
        $error = $this->validator->validate('page', [
            'content' => "```php\n<?php\n```",
            'contentFormat' => 'markdown',
            'editorProfile' => 'developer',
        ]);

        $this->assertNull($error);
    }

    public function testCompanyProfileAllowsBasicMarkdown(): void
    {
        $error = $this->validator->validate('page', [
            'content' => "## Title\n\n**Bold** and [link](/x)",
            'contentFormat' => 'markdown',
            'editorProfile' => 'company',
        ]);

        $this->assertNull($error);
    }

    public function testMinimalProfileRejectsTiptapImage(): void
    {
        $json = json_encode([
            'type' => 'doc',
            'content' => [[
                'type' => 'image',
                'attrs' => ['src' => '/img.png', 'alt' => 'x'],
            ]],
        ], JSON_THROW_ON_ERROR);

        $error = $this->validator->validate('page', [
            'content' => $json,
            'contentFormat' => 'tiptap_json',
            'editorProfile' => 'minimal',
        ]);

        $this->assertSame('Profil editora nepovoľuje obrázky.', $error);
    }

    public function testBlogProfileAllowsTiptapParagraph(): void
    {
        $json = json_encode([
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'text',
                    'text' => 'Hello',
                    'marks' => [['type' => 'bold']],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $error = $this->validator->validate('article', [
            'content' => $json,
            'contentFormat' => 'tiptap_json',
            'editorProfile' => 'blog',
        ]);

        $this->assertNull($error);
    }
}
