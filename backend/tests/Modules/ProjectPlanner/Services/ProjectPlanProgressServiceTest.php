<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\ProjectPlanner\Services;

use DateTimeImmutable;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlan;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlanItem;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlanItemVariance;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlanPhase;
use PaginiumCMS\Modules\ProjectPlanner\Services\ProjectPlanProgressService;
use PHPUnit\Framework\TestCase;

final class ProjectPlanProgressServiceTest extends TestCase
{
    private ProjectPlanProgressService $service;

    protected function setUp(): void
    {
        $this->service = new ProjectPlanProgressService();
    }

    public function testPercentMatchesOriginWeightsAndSkipsDenominator(): void
    {
        $plan = $this->plan([
            $this->item('done-1', 'done', '2026-09-15T09:00:00+02:00', '2026-09-15T10:00:00+02:00'),
            $this->item('wip-1', 'in_progress', '2026-09-20T09:00:00+02:00', null),
            $this->item('todo-1', 'planned', '2026-09-25T09:00:00+02:00', null),
            $this->item('skip-1', 'skipped', '2026-09-10T09:00:00+02:00', null),
        ]);

        $progress = $this->service->summarize($plan, new DateTimeImmutable('2026-09-12T12:00:00+02:00'));

        $this->assertSame(4, $progress->totalItems);
        $this->assertSame(3, $progress->countedItems);
        $this->assertSame(50, $progress->percent);
        $this->assertSame(1, $progress->doneCount);
        $this->assertSame(ProjectPlanItemVariance::BADGE_NONE, $progress->itemVariances['skip-1']->badge);
    }

    public function testEmptyOrAllSkippedIsZeroPercent(): void
    {
        $empty = $this->plan([]);
        $this->assertSame(0, $this->service->summarize($empty)->percent);

        $skipped = $this->plan([
            $this->item('skip-1', 'skipped', '2026-09-15T09:00:00+02:00', null),
        ]);
        $this->assertSame(0, $this->service->summarize($skipped)->percent);
        $this->assertSame(0, $this->service->summarize($skipped)->countedItems);
    }

    public function testSameCalendarDayInPlanTimezoneIsOnTime(): void
    {
        $plan = $this->plan([
            $this->item('item-1', 'done', '2026-09-15T09:00:00+02:00', '2026-09-15T23:30:00+02:00'),
        ]);

        $variance = $this->service->summarize($plan)->itemVariances['item-1'];
        $this->assertSame(ProjectPlanItemVariance::BADGE_ON_TIME, $variance->badge);
        $this->assertSame(0, $variance->days);
    }

    public function testUtcCompletionStillOnTimeForBratislavaCalendarDay(): void
    {
        $plan = $this->plan([
            $this->item('item-1', 'done', '2026-09-15T09:00:00+02:00', '2026-09-15T21:30:00Z'),
        ]);

        $variance = $this->service->summarize($plan)->itemVariances['item-1'];
        $this->assertSame(ProjectPlanItemVariance::BADGE_ON_TIME, $variance->badge);
    }

    public function testEarlyAndLateUseCalendarDays(): void
    {
        $plan = $this->plan([
            $this->item('early-1', 'done', '2026-09-15T09:00:00+02:00', '2026-09-13T18:00:00+02:00'),
            $this->item('late-1', 'done', '2026-09-15T09:00:00+02:00', '2026-09-17T08:00:00+02:00'),
        ]);

        $progress = $this->service->summarize($plan);
        $this->assertSame(ProjectPlanItemVariance::BADGE_EARLY, $progress->itemVariances['early-1']->badge);
        $this->assertSame(2, $progress->itemVariances['early-1']->days);
        $this->assertSame(ProjectPlanItemVariance::BADGE_LATE, $progress->itemVariances['late-1']->badge);
        $this->assertSame(2, $progress->itemVariances['late-1']->days);
        $this->assertSame(1, $progress->earlyCount);
        $this->assertSame(1, $progress->lateCount);
    }

    public function testOverdueUsesInstantAndDueSoonUsesSevenDayWindow(): void
    {
        $plan = $this->plan([
            $this->item('overdue-1', 'planned', '2026-09-10T09:00:00+02:00', null),
            $this->item('soon-1', 'planned', '2026-09-16T09:00:00+02:00', null),
            $this->item('later-1', 'planned', '2026-09-30T09:00:00+02:00', null),
        ]);

        $now = new DateTimeImmutable('2026-09-12T12:00:00+02:00');
        $progress = $this->service->summarize($plan, $now);

        $this->assertSame(ProjectPlanItemVariance::BADGE_OVERDUE, $progress->itemVariances['overdue-1']->badge);
        $this->assertSame(2, $progress->itemVariances['overdue-1']->days);
        $this->assertSame(ProjectPlanItemVariance::BADGE_DUE_SOON, $progress->itemVariances['soon-1']->badge);
        $this->assertSame(4, $progress->itemVariances['soon-1']->days);
        $this->assertSame(ProjectPlanItemVariance::BADGE_NONE, $progress->itemVariances['later-1']->badge);
        $this->assertSame(1, $progress->overdueCount);
        $this->assertSame(1, $progress->dueSoonCount);
    }

    public function testBlockedCountsAsZeroLikePlanned(): void
    {
        $plan = $this->plan([
            $this->item('blocked-1', 'blocked', '2026-09-20T09:00:00+02:00', null),
            $this->item('done-1', 'done', '2026-09-15T09:00:00+02:00', '2026-09-15T10:00:00+02:00'),
        ]);

        $progress = $this->service->summarize($plan, new DateTimeImmutable('2026-09-12T12:00:00+02:00'));
        $this->assertSame(50, $progress->percent);
    }

    /**
     * @param list<ProjectPlanItem> $items
     */
    private function plan(array $items): ProjectPlan
    {
        return new ProjectPlan(
            'site-relaunch-2026',
            'Corporate site relaunch',
            '',
            'Europe/Bratislava',
            '2026-08-30T10:00:00+02:00',
            '2026-08-30T12:00:00+02:00',
            'user-uuid',
            true,
            [new ProjectPlanPhase('phase-1', 'Content draft', 1)],
            $items,
        );
    }

    private function item(string $id, string $status, ?string $dueAt, ?string $completedAt): ProjectPlanItem
    {
        return new ProjectPlanItem(
            $id,
            'phase-1',
            $id,
            'article',
            $dueAt,
            $status,
            ['type' => 'article', 'slug' => null],
            $completedAt,
            '',
        );
    }
}
