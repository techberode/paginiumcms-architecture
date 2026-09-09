<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Services;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlan;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlanItem;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlanItemVariance;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlanProgress;

/**
 * Computed progress % and deadline variance (not stored on disk).
 *
 * Weights match Origin catalog scoring: done=100%, in_progress=50%,
 * planned/blocked=0%, skipped excluded from the denominator.
 */
final class ProjectPlanProgressService
{
    public function summarize(ProjectPlan $plan, ?DateTimeImmutable $now = null): ProjectPlanProgress
    {
        $now = $now ?? new DateTimeImmutable('now');
        $timezone = new DateTimeZone($plan->timezone);
        $nowInPlan = $now->setTimezone($timezone);

        $scoreTotal = 0.0;
        $counted = 0;
        $doneCount = 0;
        $overdueCount = 0;
        $dueSoonCount = 0;
        $earlyCount = 0;
        $lateCount = 0;
        $variances = [];

        foreach ($plan->items as $item) {
            if ($item->status === 'skipped') {
                $variances[$item->id] = new ProjectPlanItemVariance(
                    $item->id,
                    ProjectPlanItemVariance::BADGE_NONE,
                    0,
                );
                continue;
            }

            ++$counted;
            $scoreTotal += $this->scoreForStatus($item->status);
            if ($item->status === 'done') {
                ++$doneCount;
            }

            $variance = $this->varianceForItem($item, $nowInPlan, $timezone);
            $variances[$item->id] = $variance;

            match ($variance->badge) {
                ProjectPlanItemVariance::BADGE_OVERDUE => ++$overdueCount,
                ProjectPlanItemVariance::BADGE_DUE_SOON => ++$dueSoonCount,
                ProjectPlanItemVariance::BADGE_EARLY => ++$earlyCount,
                ProjectPlanItemVariance::BADGE_LATE => ++$lateCount,
                default => null,
            };
        }

        $percent = $counted > 0 ? (int) round(($scoreTotal / $counted) * 100) : 0;

        return new ProjectPlanProgress(
            $percent,
            count($plan->items),
            $counted,
            $doneCount,
            $overdueCount,
            $dueSoonCount,
            $earlyCount,
            $lateCount,
            $variances,
        );
    }

    private function scoreForStatus(string $status): float
    {
        return match ($status) {
            'done' => 1.0,
            'in_progress' => 0.5,
            default => 0.0,
        };
    }

    private function varianceForItem(
        ProjectPlanItem $item,
        DateTimeImmutable $nowInPlan,
        DateTimeZone $timezone,
    ): ProjectPlanItemVariance {
        if ($item->dueAt === null) {
            return new ProjectPlanItemVariance($item->id, ProjectPlanItemVariance::BADGE_NONE, 0);
        }

        $dueAt = $this->parseInTimezone($item->dueAt, $timezone);
        if ($dueAt === null) {
            return new ProjectPlanItemVariance($item->id, ProjectPlanItemVariance::BADGE_NONE, 0);
        }

        if ($item->status === 'done') {
            $completedAt = $item->completedAt !== null
                ? $this->parseInTimezone($item->completedAt, $timezone)
                : $nowInPlan;
            if ($completedAt === null) {
                return new ProjectPlanItemVariance($item->id, ProjectPlanItemVariance::BADGE_NONE, 0);
            }

            $dueDay = $this->calendarDay($dueAt, $timezone);
            $doneDay = $this->calendarDay($completedAt, $timezone);
            $days = $this->signedDayDelta($dueDay, $doneDay);

            if ($days === 0) {
                return new ProjectPlanItemVariance($item->id, ProjectPlanItemVariance::BADGE_ON_TIME, 0);
            }
            if ($days < 0) {
                return new ProjectPlanItemVariance($item->id, ProjectPlanItemVariance::BADGE_EARLY, abs($days));
            }

            return new ProjectPlanItemVariance($item->id, ProjectPlanItemVariance::BADGE_LATE, $days);
        }

        if ($nowInPlan > $dueAt) {
            $days = $this->signedDayDelta(
                $this->calendarDay($dueAt, $timezone),
                $this->calendarDay($nowInPlan, $timezone),
            );

            return new ProjectPlanItemVariance(
                $item->id,
                ProjectPlanItemVariance::BADGE_OVERDUE,
                max(1, $days),
            );
        }

        $horizon = $this->calendarDay($nowInPlan, $timezone)->add(new DateInterval('P7D'));
        $dueDay = $this->calendarDay($dueAt, $timezone);
        if ($dueDay <= $horizon) {
            $days = $this->signedDayDelta($this->calendarDay($nowInPlan, $timezone), $dueDay);

            return new ProjectPlanItemVariance(
                $item->id,
                ProjectPlanItemVariance::BADGE_DUE_SOON,
                max(0, $days),
            );
        }

        return new ProjectPlanItemVariance($item->id, ProjectPlanItemVariance::BADGE_NONE, 0);
    }

    private function parseInTimezone(string $iso, DateTimeZone $timezone): ?DateTimeImmutable
    {
        try {
            return (new DateTimeImmutable($iso))->setTimezone($timezone);
        } catch (Exception) {
            return null;
        }
    }

    private function calendarDay(DateTimeImmutable $instant, DateTimeZone $timezone): DateTimeImmutable
    {
        return $instant->setTimezone($timezone)->setTime(0, 0, 0);
    }

    /**
     * Signed calendar-day delta: positive when $to is after $from.
     */
    private function signedDayDelta(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $from->diff($to)->format('%r%a');
    }
}
