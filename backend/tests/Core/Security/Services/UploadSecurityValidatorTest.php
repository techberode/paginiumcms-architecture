<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Security\Services\UploadSecurityValidator;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Support\UploadPolicyEngineTestFactory;
use PHPUnit\Framework\TestCase;

class UploadSecurityValidatorTest extends TestCase
{
    public function testBlocksDoubleExtension(): void
    {
        $validator = $this->makeValidator([
            'blockDoubleExtensions' => true,
            'blockExecutables' => true,
            'allowedExtensions' => 'jpg,png,pdf',
            'allowedMimeTypes' => 'image/png,application/pdf',
        ]);

        $this->expectException(FlatFileException::class);
        $validator->assertFilenameAllowed('shell.php.jpg');
    }

    public function testBlocksExecutableExtension(): void
    {
        $validator = $this->makeValidator([
            'blockExecutables' => true,
            'allowedExtensions' => 'php,png',
        ]);

        $this->expectException(FlatFileException::class);
        $validator->assertFilenameAllowed('payload.php');
    }

    public function testIntersectsMimeTypesWithMediaGroup(): void
    {
        $validator = $this->makeValidator([
            'allowedMimeTypes' => 'image/png,application/pdf',
        ]);

        $resolved = $validator->resolveAllowedMimeTypes(['image/png', 'image/jpeg', 'application/pdf']);

        $this->assertSame(['image/png', 'application/pdf'], $resolved);
    }

    public function testIncompleteExtensionAllowListAcceptsMediaMimeExtensions(): void
    {
        $validator = $this->makeValidator([
            'allowedExtensions' => 'jpg',
            'blockExecutables' => true,
        ]);

        $validator->assertFilenameAllowed('photo.png');
        $this->addToAssertionCount(1);
    }

    public function testEmptyExtensionAllowListStillAcceptsImagesWhenDocumentsEnabled(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(
            static function (string $group): array {
                if ($group === 'uploadSecurity') {
                    return ['unifiedPolicyEnabled' => false, 'blockExecutables' => true];
                }
                if ($group === 'media') {
                    return [
                        'documentsEnabled' => true,
                        'documentMimeTypes' => 'application/pdf,text/plain',
                    ];
                }

                return [];
            }
        );
        $validator = new UploadSecurityValidator($settings, UploadPolicyEngineTestFactory::create($settings));
        $validator->assertFilenameAllowed('photo.png');
        $this->addToAssertionCount(1);
    }

    public function testUsesStricterUploadSizeLimit(): void
    {
        $validator = $this->makeValidator([
            'maxUploadSizeKb' => 1024,
        ]);

        $this->assertSame(1024 * 1024, $validator->resolveMaxUploadBytes(5120 * 1024));
    }

    /**
     * @param array<string, mixed> $uploadSecurity
     */
    private function makeValidator(array $uploadSecurity): UploadSecurityValidator
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(
            static function (string $group) use ($uploadSecurity): array {
                if ($group === 'uploadSecurity') {
                    return array_merge(['unifiedPolicyEnabled' => false], $uploadSecurity);
                }

                return [];
            }
        );

        return new UploadSecurityValidator($settings, UploadPolicyEngineTestFactory::create($settings));
    }
}
