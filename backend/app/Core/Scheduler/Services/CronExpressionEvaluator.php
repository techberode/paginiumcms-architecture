<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Services;

/**
 * Five-field cron matcher (minute hour day month weekday) — Iteration 29.
 */
final class CronExpressionEvaluator
{
    public function isDue(string $expression, ?\DateTimeImmutable $at = null): bool
    {
        $parts = preg_split('/\s+/', trim($expression)) ?: [];
        if (count($parts) !== 5) {
            return false;
        }

        $at ??= new \DateTimeImmutable('now');
        $fields = [
            (int) $at->format('i'),
            (int) $at->format('G'),
            (int) $at->format('j'),
            (int) $at->format('n'),
            (int) $at->format('w'),
        ];

        foreach ($parts as $index => $part) {
            if (!$this->matchesField($part, $fields[$index])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prevent duplicate execution within the same clock minute.
     */
    public function isDueSinceLastRun(string $expression, ?string $lastRunIso, ?\DateTimeImmutable $at = null): bool
    {
        if (!$this->isDue($expression, $at)) {
            return false;
        }

        if ($lastRunIso === null || $lastRunIso === '') {
            return true;
        }

        $last = strtotime($lastRunIso);
        if ($last === false) {
            return true;
        }

        $at ??= new \DateTimeImmutable('now');

        return $at->format('Y-m-d H:i') !== date('Y-m-d H:i', $last);
    }

    public function describeNextRun(string $expression, ?\DateTimeImmutable $from = null): ?string
    {
        $from ??= new \DateTimeImmutable('now');
        for ($i = 0; $i < 525600; ++$i) {
            $candidate = $from->modify('+' . $i . ' minutes');
            if ($this->isDue($expression, $candidate)) {
                return $candidate->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private function matchesField(string $expression, int $value): bool
    {
        if ($expression === '*') {
            return true;
        }

        if (str_starts_with($expression, '*/')) {
            $step = (int) substr($expression, 2);

            return $step > 0 && $value % $step === 0;
        }

        if (str_contains($expression, '-')) {
            [$from, $to] = explode('-', $expression, 2);

            return $value >= (int) $from && $value <= (int) $to;
        }

        if (str_contains($expression, ',')) {
            $values = array_map(intval(...), explode(',', $expression));

            return in_array($value, $values, true);
        }

        return (int) $expression === $value;
    }
}
