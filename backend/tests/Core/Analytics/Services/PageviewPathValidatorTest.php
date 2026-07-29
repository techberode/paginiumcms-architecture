<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Analytics\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\Analytics\Services\PageviewPathValidator;
use PHPUnit\Framework\TestCase;

final class PageviewPathValidatorTest extends TestCase
{
    public function testAcceptsPublicPath(): void
    {
        $this->assertSame('/blog/hello', PageviewPathValidator::assertValid('/blog/hello'));
    }

    public function testRejectsApiPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PageviewPathValidator::assertValid('/api/pages');
    }

    public function testRejectsTraversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PageviewPathValidator::assertValid('/../secret');
    }
}
