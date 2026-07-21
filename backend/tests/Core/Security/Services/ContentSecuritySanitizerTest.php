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
