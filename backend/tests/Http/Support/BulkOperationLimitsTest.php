<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Support;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Http\Support\BulkOperationLimits;
use PHPUnit\Framework\TestCase;

final class BulkOperationLimitsTest extends TestCase
{
    public function testAssertWithinLimitAllowsMaxIds(): void
    {
        $ids = array_map(static fn (int $i): string => 'id_' . $i, range(1, BulkOperationLimits::MAX_IDS));

        BulkOperationLimits::assertWithinLimit($ids);

        $this->addToAssertionCount(1);
    }

    public function testAssertWithinLimitRejectsAboveMax(): void
    {
        $ids = array_map(static fn (int $i): string => 'id_' . $i, range(1, BulkOperationLimits::MAX_IDS + 1));

        $this->expectException(ValidationException::class);
        BulkOperationLimits::assertWithinLimit($ids);
    }
}
