<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\TimeTracking;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\TimeTracking\Services\TimeEntryRepository;
use PHPUnit\Framework\TestCase;

final class TimeEntryRepositoryTest extends TestCase
{
    private string $baseDir;
    private TimeEntryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_time_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $this->repository = new TimeEntryRepository(new FileReader($validator), new FileWriter($validator));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testStartStopAndOwnList(): void
    {
        $started = $this->repository->start('user_ada', [
            'target' => TimeEntryRepository::TARGET_EVENT,
            'eventId' => 'event_aabbccddee',
            'note' => 'Kickoff',
        ]);

        $this->assertSame(TimeEntryRepository::SCHEMA, $started['schema']);
        $this->assertMatchesRegularExpression('/^time_[a-f0-9]{10}$/', $started['id']);
        $this->assertNull($started['endedAt']);
        $this->assertSame('event_aabbccddee', $started['eventId']);
        $this->assertNotNull($this->repository->runningForUser('user_ada'));

        $stopped = $this->repository->stop($started['id'], $started['startedAt'] + 90);
        $this->assertSame(90, $stopped['seconds']);
        $this->assertSame($started['startedAt'] + 90, $stopped['endedAt']);
        $this->assertNull($this->repository->runningForUser('user_ada'));

        $this->assertCount(1, $this->repository->list('user_ada'));
        $this->assertCount(0, $this->repository->list('user_bob'));
    }

    public function testRejectsSecondRunningTimer(): void
    {
        $this->repository->start('user_ada', [
            'target' => TimeEntryRepository::TARGET_PLAN_ITEM,
            'planId' => 'launch-site',
            'planItemId' => 'item-home',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->repository->start('user_ada', [
            'target' => TimeEntryRepository::TARGET_EVENT,
            'eventId' => 'event_aabbccddee',
        ]);
    }

    public function testSummarizeToday(): void
    {
        $now = time();
        $entry = $this->repository->start('user_ada', [
            'target' => TimeEntryRepository::TARGET_EVENT,
            'eventId' => 'event_aabbccddee',
            'startedAt' => $now - 30,
        ]);
        $this->repository->stop($entry['id'], $now);

        $summary = $this->repository->summarize($this->repository->list(), $now);
        $this->assertSame(30, $summary['todaySeconds']);
        $this->assertSame(30, $summary['byUserToday']['user_ada']);
    }

    public function testStartOnContentTarget(): void
    {
        $started = $this->repository->start('user_ada', [
            'target' => TimeEntryRepository::TARGET_CONTENT,
            'contentKind' => 'page',
            'contentSlug' => 'naradie',
        ]);

        $this->assertSame('content', $started['target']);
        $this->assertSame('page', $started['contentKind']);
        $this->assertSame('naradie', $started['contentSlug']);
        $this->assertNull($started['eventId']);
    }

    /**
     * @param string $path
     */
    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = scandir($path);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            if (is_dir($full)) {
                $this->removeTree($full);
            } else {
                unlink($full);
            }
        }
        rmdir($path);
    }
}
