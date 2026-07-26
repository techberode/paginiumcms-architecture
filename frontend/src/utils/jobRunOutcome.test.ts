import { describe, expect, it } from 'vitest';
import { interpretJobRunOutcome, outcomeBadgeClass } from './jobRunOutcome';

describe('jobRunOutcome', () => {
  it('prefers explicit outcome from backend', () => {
    expect(interpretJobRunOutcome({ success: false, reason: null, outcome: 'skipped' })).toBe('skipped');
  });

  it('maps success true to completed', () => {
    expect(interpretJobRunOutcome({ success: true, reason: null })).toBe('completed');
  });

  it('maps known skip reasons', () => {
    expect(interpretJobRunOutcome({ success: false, reason: 'no_schedule' })).toBe('skipped');
    expect(interpretJobRunOutcome({ success: false, reason: 'nothing_due' })).toBe('skipped');
  });

  it('maps unknown failure to failed', () => {
    expect(interpretJobRunOutcome({ success: false, reason: 'save_failed' })).toBe('failed');
  });

  it('returns badge classes per outcome', () => {
    expect(outcomeBadgeClass('completed')).toContain('emerald');
    expect(outcomeBadgeClass('skipped')).toContain('amber');
    expect(outcomeBadgeClass('failed')).toContain('red');
  });
});
