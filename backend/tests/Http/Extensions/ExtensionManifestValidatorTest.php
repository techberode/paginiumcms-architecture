<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Extensions;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityCatalog;
use PaginiumCMS\Http\Extensions\Services\ExtensionManifestValidator;
use PaginiumCMS\Support\Lang;
use PHPUnit\Framework\TestCase;

final class ExtensionManifestValidatorTest extends TestCase
{
    private ExtensionManifestValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        Lang::resetForTests();
        Lang::setLocale('en');
        $this->validator = new ExtensionManifestValidator();
    }

    public function testValidManifestPasses(): void
    {
        $id = $this->validator->validate($this->validManifest(), 'hello-widget');

        $this->assertSame('hello-widget', $id);
    }

    public function testUnknownHookIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown hook in manifest');

        $this->validator->validate($this->validManifest([
            'id' => 'bad-plugin',
            'name' => 'Bad',
            'hooks' => [
                'test.ping' => 'SomeClass::ping',
            ],
        ]), 'bad-plugin');
    }

    public function testMissingCapabilitiesAreRejected(): void
    {
        $manifest = $this->validManifest();
        unset($manifest['capabilities']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(Lang::get('capabilities_required', [], 'extensions'));

        $this->validator->validate($manifest, 'hello-widget');
    }

    public function testUnknownCapabilityIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(Lang::get('capabilities_unknown', ['capability' => 'shell:exec'], 'extensions'));

        $this->validator->validate($this->validManifest([
            'capabilities' => ['shell:exec'],
        ]), 'hello-widget');
    }

    public function testOutboundCapabilityIsAccepted(): void
    {
        $id = $this->validator->validate($this->validManifest([
            'capabilities' => [PluginCapabilityCatalog::CONTENT_READ, 'network:outbound:api.example.com'],
        ]), 'hello-widget');

        $this->assertSame('hello-widget', $id);
    }

    public function testManifestVersionMustBeOne(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(Lang::get('manifest_version_required', [], 'extensions'));

        $this->validator->validate($this->validManifest([
            'manifestVersion' => 2,
        ]), 'hello-widget');
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validManifest(array $overrides = []): array
    {
        return array_merge([
            'id' => 'hello-widget',
            'name' => 'Hello Widget',
            'version' => '1.0.0',
            'manifestVersion' => 1,
            'capabilities' => [PluginCapabilityCatalog::CONTENT_READ],
            'hooks' => [
                HookCatalog::EXTENSION_BOOT => 'PaginiumCMS\\Http\\Extensions\\HelloWidget\\Hooks::onBoot',
            ],
        ], $overrides);
    }
}
