<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Support;

use PaginiumCMS\Http\Support\BulkBatchResult;
use PHPUnit\Framework\TestCase;

class BulkBatchResultTest extends TestCase
{
    public function testAggregatesSuccessAndFailureCounts(): void
    {
        $batch = new BulkBatchResult();
        $batch->addSuccess('a');
        $batch->addSuccess('b');
        $batch->addFailure('c', 'Not found');

        $payload = $batch->toArray();

        $this->assertSame(3, $payload['processed']);
        $this->assertSame(2, $payload['succeeded']);
        $this->assertSame(1, $payload['failed']);
        $this->assertCount(3, $payload['results']);
        $this->assertFalse($payload['results'][2]['ok']);
        $this->assertArrayHasKey('error', $payload['results'][2]);
        $this->assertSame('Not found', $payload['results'][2]['error']);
    }
}
