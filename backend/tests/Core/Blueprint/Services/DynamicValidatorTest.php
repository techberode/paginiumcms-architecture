<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Blueprint\Services;

use PaginiumCMS\Core\Blueprint\Services\BlueprintRepository;
use PaginiumCMS\Core\Blueprint\Services\DynamicValidator;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class DynamicValidatorTest extends TestCase
{
    public function testValidatesPageBlueprintFields(): void
    {
        vfsStream::setup('root');

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));

        $repo = new BlueprintRepository($reader);
        $validator = new DynamicValidator($repo, new Validator());

        $validated = $validator->validate('page', [
            'title' => 'About',
            'slug' => 'about-us',
            'status' => 'published',
            'template' => 'default',
        ]);

        $this->assertSame('About', $validated['title']);
        $this->assertSame('about-us', $validated['slug']);
    }

    public function testRejectsInvalidSlug(): void
    {
        vfsStream::setup('root');

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));

        $repo = new BlueprintRepository($reader);
        $validator = new DynamicValidator($repo, new Validator());

        $this->expectException(ValidationException::class);
        $validator->validate('page', [
            'title' => 'Bad',
            'slug' => 'Invalid Slug!',
            'status' => 'draft',
        ]);
    }
}
