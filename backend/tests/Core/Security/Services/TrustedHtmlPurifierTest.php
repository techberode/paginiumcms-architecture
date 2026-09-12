<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Services;

use PaginiumCMS\Core\Security\Services\TrustedHtmlPurifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class TrustedHtmlPurifierTest extends TestCase
{
    public function testStripsScriptAndIframe(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn([]);

        $purifier = new TrustedHtmlPurifier($settings);
        $input = '<div>ok</div><script>alert(1)</script><iframe src="https://evil.test"></iframe>';

        $output = $purifier->purify($input);

        $this->assertStringContainsString('<div>ok</div>', $output);
        $this->assertStringNotContainsString('script', strtolower($output));
        $this->assertStringNotContainsString('iframe', strtolower($output));
    }

    public function testAllowsImageWithSrc(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn([]);

        $purifier = new TrustedHtmlPurifier($settings);
        $output = $purifier->purify('<p>Photo</p><img src="https://example.com/a.jpg" alt="A">');

        $this->assertStringContainsString('<img', $output);
        $this->assertStringContainsString('src="https://example.com/a.jpg"', $output);
    }
}
