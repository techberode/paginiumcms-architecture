<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Validation;

use PaginiumCMS\Core\Validation\DocumentSchemaRegistry;
use PaginiumCMS\Core\Validation\DocumentValidator;
use PaginiumCMS\Core\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

final class DocumentSchemaRegistryTest extends TestCase
{
    public function testDefaultsRegisterSettingsOverridesSchema(): void
    {
        $registry = DocumentSchemaRegistry::createWithDefaults();

        $this->assertTrue($registry->has(DocumentSchemaRegistry::TYPE_SETTINGS_OVERRIDES, 1));
        $this->assertSame(1, $registry->latestVersion(DocumentSchemaRegistry::TYPE_SETTINGS_OVERRIDES));
    }

    public function testUnknownSchemaVersionThrowsOnValidate(): void
    {
        $validator = new DocumentValidator(DocumentSchemaRegistry::createWithDefaults());

        $this->expectException(ValidationException::class);
        $validator->validate(DocumentSchemaRegistry::TYPE_SETTINGS_OVERRIDES, 99, ['general' => []]);
    }

    public function testInvalidDocumentShapeFailsClosed(): void
    {
        $validator = new DocumentValidator(DocumentSchemaRegistry::createWithDefaults());

        $this->expectException(ValidationException::class);
        $validator->validate(DocumentSchemaRegistry::TYPE_SETTINGS_OVERRIDES, 1, [
            'general' => 'not-an-object',
        ]);
    }

    public function testValidSettingsOverridesPassValidation(): void
    {
        $validator = new DocumentValidator(DocumentSchemaRegistry::createWithDefaults());

        $validator->validate(DocumentSchemaRegistry::TYPE_SETTINGS_OVERRIDES, 1, [
            'general' => ['siteName' => 'PaginiumCMS'],
        ]);

        $this->addToAssertionCount(1);
    }
}
