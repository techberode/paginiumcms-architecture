<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Messages\Services;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\Services\ContentEditorialReviewService;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Comments\Models\Comment;
use PaginiumCMS\Modules\Comments\Services\CommentsRepository;
use PaginiumCMS\Core\Content\Services\ContentEditorialDeskService;
use PaginiumCMS\Modules\Messages\Services\DeskInboxService;
use PaginiumCMS\Modules\Messages\Services\MessageDeskService;
use PaginiumCMS\Modules\Messages\Services\MessageRepository;
use PaginiumCMS\Modules\Messages\Services\MessageRoutingStore;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\ProjectPlanner\Contracts\ProjectPlanRepositoryInterface;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeskInboxServiceTest extends TestCase
{
    private string $baseDir;
    private DeskInboxService $desk;
    private CommentsRepository $comments;
    private TeamRepository $teams;
    private FileReader $reader;
    private FileWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_desk_inbox_' . uniqid('', true);
        mkdir($this->baseDir . '/data/index', 0777, true);
        file_put_contents(
            $this->baseDir . '/data/index/content.json',
            JsonHelper::encode(['version' => 1, 'items' => []])
        );
        $validator = new FileValidator($this->baseDir);
        $this->reader = new FileReader($validator);
        $this->writer = new FileWriter($validator);
        $this->comments = new CommentsRepository($this->reader, $this->writer);
        $this->teams = new TeamRepository($this->reader, $this->writer);
        $this->desk = $this->buildDesk(['editorialReviewEnabled' => false]);
    }

    /**
     * @param array<string, mixed> $contentSettings
     */
    private function buildDesk(array $contentSettings): DeskInboxService
    {
        /** @var SettingsRepositoryInterface&MockObject $settings */
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('sk');
        $settings->method('group')->willReturnCallback(static function (string $group) use ($contentSettings): array {
            if ($group === 'content') {
                return $contentSettings;
            }

            return [];
        });
        /** @var ProjectPlanRepositoryInterface&MockObject $plans */
        $plans = $this->createMock(ProjectPlanRepositoryInterface::class);
        $plans->method('findAll')->willReturn([]);
        $review = new ContentEditorialReviewService($settings, $this->teams, $plans);
        $index = new ContentIndexService(
            $this->reader,
            new LocalizedContentNormalizer($settings),
            new ContentStalenessService($settings),
            'data/index/content.json'
        );
        $editorialDesk = new ContentEditorialDeskService($review, $index, $settings);

        return new DeskInboxService(
            new MessageDeskService(
                new MessageRepository($this->reader, $this->writer),
                new MessageRoutingStore($this->reader, $this->writer),
                $this->teams,
                $this->createStub(UserRepository::class)
            ),
            $this->comments,
            $this->teams,
            $editorialDesk
        );
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testSupportMemberCanReplyAndClaimedItemStaysOnTheirQueue(): void
    {
        $handler = new User();
        $handler->setName('Ada');
        $handler->setEmail('ada@example.com');
        $handler->setRoles(['USER']);
        $other = new User();
        $other->setName('Bob');
        $other->setEmail('bob@example.com');
        $other->setRoles(['USER']);
        $this->teams->create('Helpdesk', TeamRepository::TYPE_SUPPORT, [$handler->getId(), $other->getId()]);

        $this->assertTrue($this->desk->canReplyComments($handler));

        $comment = new Comment('hello-world', 'Reader', 'Please clarify the second paragraph.');
        $comment->setStatus(Comment::STATUS_APPROVED);
        $this->comments->save($comment);

        $open = $this->desk->items($handler);
        $this->assertSame('comment', $open[0]['kind'] ?? null);
        $this->assertSame($comment->getId(), $open[0]['id'] ?? null);
        $this->assertStringStartsWith('/comments#comment-', (string) ($open[0]['href'] ?? ''));

        $reply = $this->desk->replyToComment($comment, $handler, 'The second paragraph is the API contract.');
        $this->assertNotNull($reply);
        $this->assertTrue($reply->isStaffReply());

        $mine = $this->desk->items($handler);
        $this->assertCount(1, $mine);
        $this->assertTrue($mine[0]['mine'] ?? false);

        $foreign = $this->desk->items($other);
        $this->assertSame([], $foreign);

        $nested = $this->desk->publicComment($comment);
        $this->assertCount(1, $nested['replies'] ?? []);
        $this->assertTrue($nested['replies'][0]['staffReply'] ?? false);
    }

    public function testPlainUserWithoutTeamCannotReply(): void
    {
        $visitorStaff = new User();
        $visitorStaff->setName('Cara');
        $visitorStaff->setEmail('cara@example.com');
        $visitorStaff->setRoles(['USER']);

        $this->assertFalse($this->desk->canReplyComments($visitorStaff));
        $this->assertSame([], $this->desk->items($visitorStaff));
    }

    public function testDeskInboxMergesEditorialReviewForTeamLeader(): void
    {
        file_put_contents(
            $this->baseDir . '/data/index/content.json',
            JsonHelper::encode([
                'version' => 1,
                'items' => [
                    [
                        'type' => 'article',
                        'slug' => 'desk-review-me',
                        'title' => 'Awaiting leader',
                        'excerpt' => '',
                        'tags' => [],
                        'category' => '',
                        'author' => 'Author',
                        'status' => ContentEditorialReviewService::STATUS_PENDING,
                        'locale' => 'sk',
                        'createdAt' => '2026-09-30T08:00:00+02:00',
                        'updatedAt' => '2026-09-30T09:00:00+02:00',
                        'path' => 'blog/desk-review-me.md',
                    ],
                ],
            ])
        );

        $leader = new User();
        $leaderId = $leader->getId();
        $team = $this->teams->create('Editorial', TeamRepository::TYPE_EDITORIAL, [$leaderId]);
        $this->teams->update((string) $team['id'], ['teamLeaderUserIds' => [$leaderId]]);

        $desk = $this->buildDesk([
            'editorialReviewEnabled' => true,
            'editorialReviewDeskEnabled' => true,
        ]);

        $items = $desk->items($leader);
        $kinds = array_map(static fn (array $row): string => (string) ($row['kind'] ?? ''), $items);

        $this->assertContains('content_review', $kinds);
        $review = array_values(array_filter($items, static fn (array $row): bool => ($row['kind'] ?? '') === 'content_review'))[0] ?? [];
        $this->assertSame('article:desk-review-me', $review['id'] ?? null);
        $this->assertSame('/articles/desk-review-me', $review['href'] ?? null);
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
