<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Extensions;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Http\Extensions\Services\ExtensionManifestValidator;
use PHPUnit\Framework\TestCase;

final class ExtensionManifestValidatorTest extends TestCase
{
    private ExtensionManifestValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ExtensionManifestValidator();
    }

    public function testValidManifestPasses(): void
    {
        $id = $this->validator->validate([
            'id' => 'hello-widget',
            'name' => 'Hello Widget',
            'version' => '1.0.0',
            'hooks' => [
                HookCatalog::EXTENSION_BOOT => 'PaginiumCMS\\Http\\Extensions\\HelloWidget\\Hooks::onBoot',
            ],
        ], 'hello-widget');

        $this->assertSame('hello-widget', $id);
    }

    public function testUnknownHookIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown hook in manifest');

        $this->validator->validate([
            'id' => 'bad-plugin',
            'name' => 'Bad',
            'version' => '1.0.0',
            'hooks' => [
                'test.ping' => 'SomeClass::ping',
            ],
        ], 'bad-plugin');
    }
}
