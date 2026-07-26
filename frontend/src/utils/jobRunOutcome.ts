// frontend/src/utils/jobRunOutcome.ts
// === Job run outcome helpers (Iteration 62) ===

import type { JobRunEntry } from '../api/jobs';

export type JobOutcome = 'completed' | 'skipped' | 'failed';

const SKIPPED_REASONS = new Set([
  'not_due',
  'no_schedule',
  'disabled',
  'nothing_due',
  'some_items_skipped',
]);

export function interpretJobRunOutcome(run: Pick<JobRunEntry, 'success' | 'reason' | 'outcome'>): JobOutcome {
  if (run.outcome === 'completed' || run.outcome === 'skipped' || run.outcome === 'failed') {
    return run.outcome;
  }

  if (run.success) {
    return 'completed';
  }

  if (run.reason && SKIPPED_REASONS.has(run.reason)) {
    return 'skipped';
  }

  return 'failed';
}

export function outcomeBadgeClass(outcome: JobOutcome): string {
  switch (outcome) {
    case 'completed':
      return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
    case 'skipped':
      return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
    default:
      return 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300';
  }
}
