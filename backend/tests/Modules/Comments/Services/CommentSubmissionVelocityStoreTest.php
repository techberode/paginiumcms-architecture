<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Comments\Services;

use PaginiumCMS\Modules\Comments\Services\CommentSubmissionVelocityStore;
use PHPUnit\Framework\TestCase;

final class CommentSubmissionVelocityStoreTest extends TestCase
{
    private string $velocityFile = '';

    protected function tearDown(): void
    {
        if ($this->velocityFile !== '' && is_file($this->velocityFile)) {
            unlink($this->velocityFile);
        }
    }

    public function testRecordPersistsBeforeCountRecent(): void
    {
        $fixedNow = strtotime('2026-08-25T12:30:00+00:00');
        $this->velocityFile = sys_get_temp_dir() . '/paginium_comment_velocity_unit_' . uniqid('', true) . '.json';
        $store = new CommentSubmissionVelocityStore(
            $this->velocityFile,
            static fn (): int => $fixedNow
        );

        $hash = hash('sha256', '203.0.113.14');

        for ($i = 0; $i < 5; $i++) {
            $store->record($hash);
        }

        $this->assertSame(5, $store->countRecent($hash, 1));
    }
}
