<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Events;

use InvalidArgumentException;
use PaginiumCMS\Core\Events\Services\EventRepository;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PHPUnit\Framework\TestCase;

final class EventRepositoryTest extends TestCase
{
    private string $baseDir;
    private EventRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_events_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $this->repository = new EventRepository(new FileReader($validator), new FileWriter($validator));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testCreateListUpdateAndDelete(): void
    {
        $created = $this->repository->create([
            'title' => 'Open day',
            'startsAt' => 1_800_000_000,
            'location' => 'Hall A',
            'status' => EventRepository::STATUS_PUBLISHED,
        ]);

        $this->assertSame(EventRepository::SCHEMA, $created['schema']);
        $this->assertMatchesRegularExpression('/^event_[a-f0-9]{10}$/', $created['id']);
        $this->assertSame('open-day', $created['slug']);
        $this->assertSame(EventRepository::STATUS_PUBLISHED, $created['status']);
        $this->assertNull($created['endsAt']);
        $this->assertNull($created['projectPlanId']);

        $listed = $this->repository->list();
        $this->assertCount(1, $listed);
        $this->assertSame($created['id'], $listed[0]['id']);

        $updated = $this->repository->update($created['id'], [
            'title' => 'Open day 2',
            'endsAt' => 1_800_003_600,
            'projectPlanId' => 'launch-site',
            'body' => 'Welcome.',
        ]);
        $this->assertSame('Open day 2', $updated['title']);
        $this->assertSame(1_800_003_600, $updated['endsAt']);
        $this->assertSame('launch-site', $updated['projectPlanId']);
        $this->assertSame('Welcome.', $updated['body']);

        $this->repository->delete($created['id']);
        $this->assertNull($this->repository->get($created['id']));
        $this->assertSame([], $this->repository->list());
    }

    public function testUniqueSlugAndEndBeforeStartRejected(): void
    {
        $first = $this->repository->create([
            'title' => 'Meetup',
            'startsAt' => 1_700_000_000,
        ]);
        $second = $this->repository->create([
            'title' => 'Meetup',
            'startsAt' => 1_700_000_100,
        ]);
        $this->assertSame('meetup', $first['slug']);
        $this->assertSame('meetup-2', $second['slug']);

        $this->expectException(InvalidArgumentException::class);
        $this->repository->create([
            'title' => 'Bad window',
            'startsAt' => 1_700_000_200,
            'endsAt' => 1_700_000_100,
        ]);
    }

    public function testTitleRequired(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->create(['startsAt' => 1_700_000_000]);
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
