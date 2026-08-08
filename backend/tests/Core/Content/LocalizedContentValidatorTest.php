<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\LocalizedContentValidator;
use PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry;
use PaginiumCMS\Core\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

final class LocalizedContentValidatorTest extends TestCase
{
    private LocalizedContentValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new LocalizedContentValidator(new SupportedLocalesRegistry(dirname(__DIR__, 4)));
    }

    public function testDraftLocaleAllowsEmptyTitle(): void
    {
        $this->validator->validateWritePayload([
            'locale' => 'en',
            'title' => '',
            'status' => 'draft',
        ]);

        $this->addToAssertionCount(1);
    }

    public function testPublishedLocaleRequiresTitle(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validateWritePayload([
            'locale' => 'en',
            'title' => '',
            'status' => 'published',
        ]);
    }

    public function testUnsupportedLocaleRejected(): void
    {
        try {
            $this->validator->validateWritePayload([
                'locale' => 'xx',
                'title' => 'Test',
                'status' => 'draft',
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertStringContainsString('Unsupported locale', $e->getFlatMessages()[0] ?? '');
        }
    }

    public function testBulkLocalizedContentRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validateWritePayload([
            'locale' => 'en',
            'localizedContent' => ['en' => ['title' => 'Hack']],
            'title' => 'EN',
        ]);
    }

    public function testTranslationProposalCannotAutoPublish(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validateWritePayload([
            'locale' => 'en',
            'title' => 'Translated title',
            'status' => 'published',
            'proposalSource' => 'translation',
        ]);
    }

    public function testEditorPublishWithoutProposalSourceStillAllowed(): void
    {
        $this->validator->validateWritePayload([
            'locale' => 'en',
            'title' => 'Manual publish',
            'status' => 'published',
        ]);

        $this->addToAssertionCount(1);
    }
}
