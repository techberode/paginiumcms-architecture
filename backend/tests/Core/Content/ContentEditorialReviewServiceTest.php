<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\Services\ContentEditorialReviewService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\ProjectPlanner\Contracts\ProjectPlanRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PHPUnit\Framework\TestCase;

final class ContentEditorialReviewServiceTest extends TestCase
{
    private string $baseDir = '';
    private TeamRepository $teams;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_editorial_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $this->teams = new TeamRepository(new FileReader($validator), new FileWriter($validator));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testNonLeaderPublishBecomesPendingReview(): void
    {
        $team = $this->teams->create('News', TeamRepository::TYPE_EDITORIAL, ['author-1', 'leader-1']);
        $this->teams->update((string) $team['id'], ['teamLeaderUserIds' => ['leader-1']]);

        $service = $this->service(enabled: true);
        $author = $this->user('author-1');

        $resolved = $service->resolveRequestedStatus($author, 'published', 'draft');

        $this->assertSame(ContentEditorialReviewService::STATUS_PENDING, $resolved);
    }

    public function testLeaderMayPublishDirectly(): void
    {
        $team = $this->teams->create('News', TeamRepository::TYPE_EDITORIAL, ['leader-1']);
        $this->teams->update((string) $team['id'], ['teamLeaderUserIds' => ['leader-1']]);

        $service = $this->service(enabled: true);
        $leader = $this->user('leader-1');

        $resolved = $service->resolveRequestedStatus($leader, 'published', 'draft');

        $this->assertSame('published', $resolved);
    }

    public function testWorkflowInactiveWhenNoTeams(): void
    {
        $service = $this->service(enabled: true);
        $author = $this->user('author-1');

        $this->assertFalse($service->isWorkflowActive());
        $this->assertSame('published', $service->resolveRequestedStatus($author, 'published', 'draft'));
    }

    private function service(bool $enabled): ContentEditorialReviewService
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group) use ($enabled): array {
            if ($group === 'content') {
                return ['editorialReviewEnabled' => $enabled];
            }

            return [];
        });

        $plans = $this->createMock(ProjectPlanRepositoryInterface::class);
        $plans->method('findAll')->willReturn([]);

        return new ContentEditorialReviewService($settings, $this->teams, $plans);
    }

    private function user(string $id): User
    {
        return new class($id) extends User {
            public function __construct(private readonly string $fixedId)
            {
                parent::__construct();
            }

            public function getId(): string
            {
                return $this->fixedId;
            }
        };
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
