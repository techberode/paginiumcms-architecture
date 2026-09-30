<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\Services\ContentEditorialDeskService;
use PaginiumCMS\Core\Content\Services\ContentEditorialReviewService;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\ProjectPlanner\Contracts\ProjectPlanRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ContentEditorialDeskServiceTest extends TestCase
{
    private string $baseDir = '';
    private TeamRepository $teams;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_editorial_desk_' . uniqid('', true);
        mkdir($this->baseDir . '/data/index', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $this->teams = new TeamRepository(new FileReader($validator), new FileWriter($validator));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testLeaderReceivesPendingReviewItems(): void
    {
        $leader = new User();
        $leaderId = $leader->getId();
        $team = $this->teams->create('News', TeamRepository::TYPE_EDITORIAL, [$leaderId]);
        $this->teams->update((string) $team['id'], ['teamLeaderUserIds' => [$leaderId]]);

        $this->writeIndex([
            [
                'type' => 'article',
                'slug' => 'beta-47',
                'title' => 'Beta 47',
                'excerpt' => '',
                'tags' => [],
                'category' => '',
                'author' => 'Admin',
                'status' => 'pending_review',
                'locale' => 'sk',
                'createdAt' => '2026-09-29T10:00:00+02:00',
                'updatedAt' => '2026-09-30T10:00:00+02:00',
                'path' => 'blog/beta-47.md',
            ],
        ]);

        /** @var SettingsRepositoryInterface&MockObject $settings */
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('sk');
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            if ($group === 'content') {
                return ['editorialReviewEnabled' => true, 'editorialReviewDeskEnabled' => true];
            }

            return [];
        });

        /** @var ProjectPlanRepositoryInterface&MockObject $plans */
        $plans = $this->createMock(ProjectPlanRepositoryInterface::class);
        $plans->method('findAll')->willReturn([]);

        $review = new ContentEditorialReviewService($settings, $this->teams, $plans);
        $service = new ContentEditorialDeskService($review, $this->contentIndex($settings), $settings);
        $items = $service->pendingReviewDeskItems($leader);

        $this->assertCount(1, $items);
        $this->assertSame('content_review', $items[0]['kind']);
        $this->assertSame('article:beta-47', $items[0]['id']);
        $this->assertSame('/articles/beta-47', $items[0]['href']);
    }

    public function testNonLeaderGetsEmptyList(): void
    {
        /** @var SettingsRepositoryInterface&MockObject $settings */
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('sk');
        $settings->method('group')->willReturn(['editorialReviewEnabled' => true, 'editorialReviewDeskEnabled' => true]);

        /** @var ProjectPlanRepositoryInterface&MockObject $plans */
        $plans = $this->createMock(ProjectPlanRepositoryInterface::class);
        $plans->method('findAll')->willReturn([]);

        $this->teams->create('News', TeamRepository::TYPE_EDITORIAL, ['other-user']);

        $review = new ContentEditorialReviewService($settings, $this->teams, $plans);
        $service = new ContentEditorialDeskService($review, $this->contentIndex($settings), $settings);

        $this->assertSame([], $service->pendingReviewDeskItems(new User()));
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function writeIndex(array $items): void
    {
        file_put_contents(
            $this->baseDir . '/data/index/content.json',
            JsonHelper::encode(['version' => 1, 'items' => $items])
        );
    }

    private function contentIndex(SettingsRepositoryInterface $settings): ContentIndexService
    {
        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);

        if (!is_file($this->baseDir . '/data/index/content.json')) {
            $this->writeIndex([]);
        }

        return new ContentIndexService(
            $reader,
            new LocalizedContentNormalizer($settings),
            new ContentStalenessService($settings),
            'data/index/content.json'
        );
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($path);
    }
}
