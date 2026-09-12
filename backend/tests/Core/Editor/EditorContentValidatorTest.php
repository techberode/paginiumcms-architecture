<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\EditorComponentRegistry;
use PaginiumCMS\Core\Editor\Services\EditorContentValidator;
use PaginiumCMS\Core\Editor\Services\EditorProfileService;
use PaginiumCMS\Core\Editor\Services\ExternalEmbedContentService;
use PaginiumCMS\Core\Editor\Services\ExternalEmbedShortcode;
use PaginiumCMS\Core\Editor\Services\HtmlSafeShortcode;
use PaginiumCMS\Core\Editor\Services\TrustedHtmlContentService;
use PaginiumCMS\Core\Security\Services\TrustedHtmlPurifier;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Extensions\Contracts\PluginManagerInterface;
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
            new FileWriter($validator),
            \PaginiumCMS\Tests\Support\StorageTestHelper::localStorage($baseDir),
            new Validator(),
            'data/settings.json'
        );
        $plugins = $this->createMock(PluginManagerInterface::class);
        $plugins->method('listEnabledEditorComponents')->willReturn([]);
        $components = new EditorComponentRegistry($plugins);
        $auth = $this->createMock(AuthorizationInterface::class);
        $auth->method('hasPermission')->willReturn(false);
        $trustedHtml = new TrustedHtmlContentService($settings, $auth, new TrustedHtmlPurifier($settings));
        $externalEmbed = new ExternalEmbedContentService($settings, $auth, new ExternalEmbedShortcode());
        $this->validator = new EditorContentValidator(
            new EditorProfileService($settings, $components),
            $components,
            $trustedHtml,
            $externalEmbed,
            new HtmlSafeShortcode(),
        );
    }

    public function testBlogProfileAllowsMarkdownCodeBlock(): void
    {
        $error = $this->validator->validate('article', [
            'content' => "## Ukážka\n\n```markdown\n# title\n```",
            'contentFormat' => 'markdown',
            'editorProfile' => 'blog',
        ]);

        $this->assertNull($error);
    }

    public function testMinimalProfileAllowsMarkdownImage(): void
    {
        $error = $this->validator->validate('page', [
            'content' => 'Text ![alt](/img.png)',
            'contentFormat' => 'markdown',
            'editorProfile' => 'minimal',
        ]);

        $this->assertNull($error);
    }

    public function testBlogProfileAllowsHtmlTable(): void
    {
        $error = $this->validator->validate('article', [
            'content' => '<p>x</p><table><tr><td>1</td></tr></table>',
            'contentFormat' => 'html',
            'editorProfile' => 'blog',
        ]);

        $this->assertNull($error);
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

    public function testMinimalProfileAllowsTiptapImage(): void
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

        $this->assertNull($error);
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

    public function testMarkdownRejectsRawHtmlTag(): void
    {
        $error = $this->validator->validate('article', [
            'content' => "Text\n\n<div>raw</div>",
            'contentFormat' => 'markdown',
            'editorProfile' => 'blog',
        ]);

        $this->assertSame('Markdown obsah nesmie obsahovať raw HTML tagy.', $error);
    }

    public function testMarkdownRejectsHtmlSafeBlockWithoutPermission(): void
    {
        $error = $this->validator->validate('article', [
            'content' => ":::html-safe\n<div>ok</div>\n:::\n",
            'contentFormat' => 'markdown',
            'editorProfile' => 'developer',
        ]);

        $this->assertSame(
            'Trusted HTML bloky nie sú povolené pre tento účet alebo sú vypnuté v nastaveniach.',
            $error
        );
    }

    public function testMarkdownAllowsCalloutBlock(): void
    {
        $error = $this->validator->validate('article', [
            'content' => ":::note\nSafe text\n:::\n",
            'contentFormat' => 'markdown',
            'editorProfile' => 'blog',
        ]);

        $this->assertNull($error);
    }

    public function testMarkdownRejectsCalloutWithScript(): void
    {
        $error = $this->validator->validate('article', [
            'content' => ":::warning\n<script>x</script>\n:::\n",
            'contentFormat' => 'markdown',
            'editorProfile' => 'developer',
        ]);

        $this->assertSame('Callout bloky nepovoľujú skripty ani iframe.', $error);
    }

    public function testMarkdownRejectsEmbedBlockWithoutPermission(): void
    {
        $error = $this->validator->validate('article', [
            'content' => ":::embed\nprovider: youtube\nid: dQw4w9WgXcQ\n:::\n",
            'contentFormat' => 'markdown',
            'editorProfile' => 'developer',
        ]);

        $this->assertSame(
            'Externé embed bloky nie sú povolené pre tento účet alebo sú vypnuté v nastaveniach.',
            $error
        );
    }

    public function testHtmlRejectsScriptTag(): void
    {
        $error = $this->validator->validate('page', [
            'content' => '<p>ok</p><script>alert(1)</script>',
            'contentFormat' => 'html',
            'editorProfile' => 'developer',
        ]);

        $this->assertSame('Obsah nepovoľuje vložené skripty alebo iframe.', $error);
    }

    public function testInvalidEditorProfileRejected(): void
    {
        $error = $this->validator->validate('page', [
            'content' => 'Hello',
            'contentFormat' => 'markdown',
            'editorProfile' => 'unknown-profile',
        ]);

        $this->assertSame('Neplatný editor profil.', $error);
    }
}
