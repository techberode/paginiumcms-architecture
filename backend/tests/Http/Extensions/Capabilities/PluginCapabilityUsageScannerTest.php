<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Extensions\Capabilities;

use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityCatalog;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityUsageScanner;
use PHPUnit\Framework\TestCase;

final class PluginCapabilityUsageScannerTest extends TestCase
{
    public function testContentFacadeRequiresContentCapability(): void
    {
        $scanner = new PluginCapabilityUsageScanner();
        $php = <<<'PHP'
<?php
$runtime->content()->get('page', 'home');
PHP;

        $errors = $scanner->scanPhp($php, [PluginCapabilityCatalog::ADMIN_UI_EDITOR_BLOCK]);
        $this->assertNotSame([], $errors);
        $this->assertStringContainsString('content()', $errors[0]);
    }

    public function testContentFacadeAllowedWhenDeclared(): void
    {
        $scanner = new PluginCapabilityUsageScanner();
        $php = <<<'PHP'
<?php
$runtime->content()->get('page', 'home');
PHP;

        $this->assertSame([], $scanner->scanPhp($php, [PluginCapabilityCatalog::CONTENT_READ]));
    }

    public function testNetworkCallRequiresOutboundCapability(): void
    {
        $scanner = new PluginCapabilityUsageScanner();
        $php = <<<'PHP'
<?php
file_get_contents('https://example.com/');
PHP;

        $errors = $scanner->scanPhp($php, [PluginCapabilityCatalog::CONTENT_READ]);
        $this->assertNotSame([], $errors);
        $this->assertStringContainsString('network:outbound', $errors[0]);
    }

    public function testOutboundCapabilityAllowsFileGetContents(): void
    {
        $scanner = new PluginCapabilityUsageScanner();
        $php = <<<'PHP'
<?php
file_get_contents('https://api.example.com/');
PHP;

        $this->assertSame([], $scanner->scanPhp($php, ['network:outbound:api.example.com']));
    }

    public function testRawContentRepositoryIsForbidden(): void
    {
        $scanner = new PluginCapabilityUsageScanner();
        $php = <<<'PHP'
<?php
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
PHP;

        $errors = $scanner->scanPhp($php, [PluginCapabilityCatalog::CONTENT_READ]);
        $this->assertNotSame([], $errors);
        $this->assertStringContainsString('ContentRepositoryInterface', $errors[0]);
    }
}
